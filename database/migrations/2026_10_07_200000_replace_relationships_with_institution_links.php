<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the named `relationships` + polymorphic `relationshipables` pair and the two type
 * sibling flags with explicit institution and institution-type links. Access is preserved:
 * a cross-tenant type link used to grant pagrindinis → padalinys in both orientations, so it
 * becomes two rows; sibling flags become self-links.
 */
return new class extends Migration
{
    /** Legacy relationship slug => kind. Anything else becomes `related`. */
    private const KIND_BY_SLUG = [
        'paprastas-patariamasis' => 'advisory',
        'confirmation-of-composition' => 'approves_composition',
        'further-approval' => 'forwards_issues',
    ];

    public function up(): void
    {
        Schema::create('institution_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('source_institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('target_institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('kind')->default('related');
            $table->boolean('mutual')->default(false);
            $table->timestamps();

            $table->unique(['source_institution_id', 'target_institution_id'], 'institution_links_pair_unique');
        });

        Schema::create('institution_type_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_type_id')->constrained('institution_types')->cascadeOnDelete();
            $table->foreignId('target_type_id')->constrained('institution_types')->cascadeOnDelete();
            $table->string('kind')->default('related');
            $table->boolean('mutual')->default(false);
            $table->boolean('cross_tenant')->default(false);
            $table->timestamps();

            $table->unique(['source_type_id', 'target_type_id', 'cross_tenant'], 'institution_type_links_pair_unique');
        });

        $this->convertLegacyData();

        DB::table('permissions')
            ->where('name', 'like', 'relationshipables.%')
            ->orWhereIn('name', ['relationships.create.own', 'relationships.create.padalinys', 'relationships.read.own', 'relationships.read.padalinys', 'relationships.update.own', 'relationships.update.padalinys', 'relationships.delete.own', 'relationships.delete.padalinys'])
            ->pluck('id')
            ->each(function ($id): void {
                DB::table('role_has_permissions')->where('permission_id', $id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $id)->delete();
                DB::table('permissions')->where('id', $id)->delete();
            });

        Schema::dropIfExists('relationshipables');
        Schema::dropIfExists('relationships');
    }

    public function down(): void
    {
        Schema::create('relationships', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('relationshipables', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('relationship_id')->constrained('relationships');
            $table->string('relationshipable_type', 125);
            $table->string('relationshipable_id', 26);
            $table->string('related_model_id', 26);
            $table->string('scope', 125)->default('within-tenant');
            $table->boolean('bidirectional')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        $relationshipIds = [];
        $relationshipFor = function (string $kind) use (&$relationshipIds): int {
            return $relationshipIds[$kind] ??= DB::table('relationships')->insertGetId([
                'name' => $kind,
                'slug' => array_search($kind, self::KIND_BY_SLUG, true) ?: $kind,
            ]);
        };

        foreach (DB::table('institution_links')->get() as $link) {
            DB::table('relationshipables')->insert([
                'relationship_id' => $relationshipFor($link->kind),
                'relationshipable_type' => 'institution',
                'relationshipable_id' => $link->source_institution_id,
                'related_model_id' => $link->target_institution_id,
                'bidirectional' => $link->mutual,
            ]);
        }

        $flags = [];
        foreach (DB::table('institution_type_links')->get() as $link) {
            if ((int) $link->source_type_id === (int) $link->target_type_id && (bool) $link->mutual !== (bool) $link->cross_tenant) {
                $flags[$link->source_type_id][$link->cross_tenant ? 'enable_cross_tenant_sibling_relationships' : 'enable_sibling_relationships'] = true;

                continue;
            }

            DB::table('relationshipables')->insert([
                'relationship_id' => $relationshipFor($link->kind),
                'relationshipable_type' => 'institution_type',
                'relationshipable_id' => (string) $link->source_type_id,
                'related_model_id' => (string) $link->target_type_id,
                'scope' => $link->cross_tenant ? 'cross-tenant' : 'within-tenant',
                'bidirectional' => $link->mutual,
            ]);
        }

        foreach ($flags as $typeId => $typeFlags) {
            $attributes = json_decode((string) DB::table('institution_types')->where('id', $typeId)->value('extra_attributes'), true);
            DB::table('institution_types')->where('id', $typeId)->update([
                'extra_attributes' => json_encode([...(is_array($attributes) ? $attributes : []), ...$typeFlags]),
            ]);
        }

        Schema::dropIfExists('institution_type_links');
        Schema::dropIfExists('institution_links');
    }

    private function convertLegacyData(): void
    {
        $now = now();
        $institutionLinks = [];
        $typeLinks = [];

        $addTypeLink = function (int $source, int $target, string $kind, bool $mutual, bool $crossTenant) use (&$typeLinks): void {
            $key = "{$source}:{$target}:".(int) $crossTenant;
            $typeLinks[$key] = [
                'source_type_id' => $source,
                'target_type_id' => $target,
                'kind' => $typeLinks[$key]['kind'] ?? $kind,
                'mutual' => $mutual || ($typeLinks[$key]['mutual'] ?? false),
                'cross_tenant' => $crossTenant,
            ];
        };

        if (Schema::hasTable('relationshipables')) {
            $institutionIds = DB::table('institutions')->pluck('id')->flip();
            $typeIds = DB::table('institution_types')->pluck('id')->map(fn ($id) => (int) $id)->flip();
            $slugs = Schema::hasTable('relationships') ? DB::table('relationships')->pluck('slug', 'id') : collect();

            foreach (DB::table('relationshipables')->get() as $row) {
                $kind = self::KIND_BY_SLUG[$slugs[$row->relationship_id] ?? ''] ?? 'related';
                $mutual = (bool) $row->bidirectional;

                if ($row->relationshipable_type === 'institution') {
                    if ($row->relationshipable_id === $row->related_model_id || ! $institutionIds->has($row->relationshipable_id) || ! $institutionIds->has($row->related_model_id)) {
                        continue;
                    }

                    $key = "{$row->relationshipable_id}:{$row->related_model_id}";
                    $institutionLinks[$key] = [
                        'source_institution_id' => $row->relationshipable_id,
                        'target_institution_id' => $row->related_model_id,
                        'kind' => $institutionLinks[$key]['kind'] ?? $kind,
                        'mutual' => $mutual || ($institutionLinks[$key]['mutual'] ?? false),
                    ];

                    continue;
                }

                $source = (int) $row->relationshipable_id;
                $target = (int) $row->related_model_id;

                if ($row->relationshipable_type !== 'institution_type' || ! $typeIds->has($source) || ! $typeIds->has($target)) {
                    continue;
                }

                $crossTenant = $row->scope === 'cross-tenant';
                $addTypeLink($source, $target, $kind, $mutual, $crossTenant);

                if ($crossTenant && $source !== $target) {
                    $addTypeLink($target, $source, $kind, $mutual, true);
                }
            }
        }

        foreach (DB::table('institution_types')->whereNotNull('extra_attributes')->get(['id', 'extra_attributes']) as $type) {
            $attributes = json_decode((string) $type->extra_attributes, true);

            if (! is_array($attributes) || ! array_intersect_key($attributes, array_flip(['enable_sibling_relationships', 'enable_cross_tenant_sibling_relationships']))) {
                continue;
            }

            if ($attributes['enable_sibling_relationships'] ?? false) {
                $addTypeLink((int) $type->id, (int) $type->id, 'related', true, false);
            }

            if ($attributes['enable_cross_tenant_sibling_relationships'] ?? false) {
                $addTypeLink((int) $type->id, (int) $type->id, 'related', false, true);
            }

            unset($attributes['enable_sibling_relationships'], $attributes['enable_cross_tenant_sibling_relationships']);

            DB::table('institution_types')->where('id', $type->id)->update([
                'extra_attributes' => $attributes === [] ? null : json_encode($attributes),
            ]);
        }

        $stamp = fn (array $row) => [...$row, 'created_at' => $now, 'updated_at' => $now];

        DB::table('institution_links')->insert(array_map($stamp, array_values($institutionLinks)));
        DB::table('institution_type_links')->insert(array_map($stamp, array_values($typeLinks)));
    }
};
