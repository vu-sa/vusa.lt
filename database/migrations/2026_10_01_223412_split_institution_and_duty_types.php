<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $types = DB::table('types')->get()->keyBy('id');
        $domains = ['institution' => 'institution_types', 'duty' => 'duty_types'];
        $aliases = ['App\\Models\\Institution' => 'institution', 'App\\Models\\Duty' => 'duty'];

        foreach ($types as $type) {
            $type->model_type = $aliases[$type->model_type] ?? $type->model_type;
            if (! isset($domains[$type->model_type])) {
                throw new RuntimeException("Unknown domain for type {$type->id}.");
            }

            $seen = [];
            $cursor = $type;
            while ($cursor->parent_id !== null) {
                if (isset($seen[$cursor->id]) || ! isset($types[$cursor->parent_id])) {
                    throw new RuntimeException("Invalid hierarchy for type {$type->id}.");
                }
                $seen[$cursor->id] = true;
                $cursor = $types[$cursor->parent_id];
                $parentDomain = $aliases[$cursor->model_type] ?? $cursor->model_type;
                if ($parentDomain !== $type->model_type) {
                    throw new RuntimeException("Cross-domain parent for type {$type->id}.");
                }
            }
        }

        $assignments = DB::table('typeables')->get();
        $owners = [
            'institution' => DB::table('institutions')->pluck('id')->flip(),
            'duty' => DB::table('duties')->pluck('id')->flip(),
        ];
        foreach ($assignments as $assignment) {
            $assignment->typeable_type = $aliases[$assignment->typeable_type] ?? $assignment->typeable_type;
            $type = $types[$assignment->type_id] ?? null;
            if ($type === null || $type->model_type !== $assignment->typeable_type) {
                throw new RuntimeException('Invalid or cross-domain type assignment.');
            }
        }

        foreach (['role_type', 'role_can_attach_types'] as $table) {
            foreach (DB::table($table)->get() as $grant) {
                if (($types[$grant->type_id]->model_type ?? null) !== 'duty') {
                    throw new RuntimeException("Non-duty role link in {$table}.");
                }
            }
        }

        foreach ($this->referenceColumns() as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $kind => $id) {
                if (! Schema::hasColumn($table, $kind)) {
                    continue;
                }
                foreach (DB::table($table)->whereIn($kind, ['type', 'App\\Models\\Type'])->get() as $reference) {
                    if (! isset($types[$reference->{$id}])) {
                        throw new RuntimeException("Unresolved type reference in {$table}.{$kind}.");
                    }
                    if ($table === 'relationshipables') {
                        $source = $types[$reference->{$id}];
                        $target = $types[$reference->related_model_id] ?? null;
                        if ($source->model_type !== 'institution' || $target === null || $target->model_type !== 'institution') {
                            throw new RuntimeException('Type relationships must connect institution types.');
                        }
                    }
                    if ($table === 'duty_responsibilities' && $types[$reference->{$id}]->model_type !== 'institution') {
                        throw new RuntimeException('Responsibilities must reference institution types.');
                    }
                }
            }
        }

        foreach ($domains as $typeTable) {
            Schema::create($typeTable, function (Blueprint $table) use ($typeTable): void {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained($typeTable)->restrictOnDelete();
                $table->json('title')->nullable();
                $table->json('description')->nullable();
                $table->string('slug', 125)->nullable()->index();
                $table->json('extra_attributes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        foreach ($domains as $domain => $typeTable) {
            Schema::create("{$domain}_{$domain}_type", function (Blueprint $table) use ($domain, $typeTable): void {
                $table->char("{$domain}_id", 26);
                $table->foreign("{$domain}_id")->references('id')->on($domain === 'duty' ? 'duties' : 'institutions')->cascadeOnDelete();
                $table->foreignId("{$domain}_type_id")->constrained($typeTable)->restrictOnDelete();
                $table->primary(["{$domain}_id", "{$domain}_type_id"]);
            });
        }

        foreach (['duty_type_role', 'role_can_attach_duty_types'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->char('role_id', 26);
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
                $table->foreignId('duty_type_id')->constrained('duty_types')->restrictOnDelete();
                $table->timestamps();
                $table->primary(['role_id', 'duty_type_id']);
            });
        }

        Schema::create('type_assignment_orphans', function (Blueprint $table): void {
            $table->id();
            $table->json('assignment');
            $table->string('reason');
            $table->timestamp('archived_at');
        });

        DB::transaction(function () use ($types, $domains, $assignments, $owners): void {
            foreach ($types as $type) {
                $row = (array) $type;
                unset($row['model_type']);
                $row['parent_id'] = null;
                DB::table($domains[$type->model_type])->insert($row);
            }

            foreach ($types as $type) {
                DB::table($domains[$type->model_type])->where('id', $type->id)->update(['parent_id' => $type->parent_id]);
            }

            foreach ($assignments as $assignment) {
                $domain = $assignment->typeable_type;
                if (! isset($owners[$domain][$assignment->typeable_id])) {
                    DB::table('type_assignment_orphans')->insert([
                        'assignment' => json_encode($assignment, JSON_THROW_ON_ERROR),
                        'reason' => 'Owner no longer exists',
                        'archived_at' => now(),
                    ]);
                    continue;
                }
                DB::table("{$domain}_{$domain}_type")->insert([
                    "{$domain}_id" => $assignment->typeable_id,
                    "{$domain}_type_id" => $assignment->type_id,
                ]);
            }

            foreach (['role_type' => 'duty_type_role', 'role_can_attach_types' => 'role_can_attach_duty_types'] as $old => $new) {
                foreach (DB::table($old)->get() as $grant) {
                    DB::table($new)->insertOrIgnore([
                        'role_id' => $grant->role_id,
                        'duty_type_id' => $grant->type_id,
                        'created_at' => $grant->created_at,
                        'updated_at' => $grant->updated_at,
                    ]);
                }
            }

            foreach ($this->referenceColumns() as $table => $columns) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                foreach ($columns as $kind => $id) {
                    if (! Schema::hasColumn($table, $kind)) {
                        continue;
                    }
                    foreach ($types as $type) {
                        DB::table($table)->whereIn($kind, ['type', 'App\\Models\\Type'])->where($id, $type->id)
                            ->update([$kind => $type->model_type.'_type']);
                    }
                }
            }

            foreach (DB::table('permissions')->where('name', 'like', 'types.%')->get() as $permission) {
                foreach (['institutionTypes', 'dutyTypes'] as $resource) {
                    $name = $resource.substr($permission->name, strlen('types'));
                    $id = DB::table('permissions')->where('name', $name)->where('guard_name', $permission->guard_name)->value('id');
                    if ($id === null) {
                        $id = (string) Str::ulid();
                        DB::table('permissions')->insert([
                            'id' => $id, 'name' => $name, 'guard_name' => $permission->guard_name,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    foreach (['role_has_permissions', 'model_has_permissions'] as $table) {
                        foreach (DB::table($table)->where('permission_id', $permission->id)->get() as $grant) {
                            $row = (array) $grant;
                            $row['permission_id'] = $id;
                            DB::table($table)->insertOrIgnore($row);
                        }
                    }
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('Restore the pre-cutover backup or use a forward fix; independently created type IDs cannot be safely merged.');
    }

    /** @return array<string, array<string, string>> */
    private function referenceColumns(): array
    {
        return [
            'activity_log' => ['subject_type' => 'subject_id', 'root_subject_type' => 'root_subject_id', 'causer_type' => 'causer_id'],
            'comments' => ['commentable_type' => 'commentable_id'],
            'fileable_files' => ['fileable_type' => 'fileable_id'],
            'sharepoint_fileables' => ['fileable_type' => 'fileable_id'],
            'media' => ['model_type' => 'model_id'],
            'relationshipables' => ['relationshipable_type' => 'relationshipable_id'],
            'duty_responsibilities' => ['scope_type' => 'scope_id'],
            'tasks' => ['taskable_type' => 'taskable_id'],
            'workspace_links' => ['linkable_type' => 'linkable_id'],
            'public_urls' => ['urlable_type' => 'urlable_id'],
            'approvals' => ['approvable_type' => 'approvable_id'],
            'approval_flows' => ['flowable_type' => 'flowable_id'],
            'model_has_roles' => ['model_type' => 'model_id'],
            'model_has_permissions' => ['model_type' => 'model_id'],
        ];
    }
};
