<?php

use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

pest()->use(RefreshDatabase::class);

function institutionLinksMigration(): object
{
    return require base_path('database/migrations/2026_10_07_200000_replace_relationships_with_institution_links.php');
}

beforeEach(function (): void {
    // Back to the legacy schema, as production is before the migration.
    institutionLinksMigration()->down();

    $tenant = Tenant::query()->first();
    $this->source = Institution::factory()->for($tenant)->create();
    $this->target = Institution::factory()->for($tenant)->create();
    $this->senate = InstitutionType::factory()->create(['extra_attributes' => ['enable_sibling_relationships' => true, 'governance_scope' => 'vu']]);
    $this->council = InstitutionType::factory()->create();
    $this->commission = InstitutionType::factory()->create(['extra_attributes' => ['enable_cross_tenant_sibling_relationships' => true]]);

    $relationship = fn (string $slug) => DB::table('relationships')->insertGetId(['name' => $slug, 'slug' => $slug]);
    DB::table('relationshipables')->insert([
        ['relationship_id' => $relationship('confirmation-of-composition'), 'relationshipable_type' => 'institution', 'relationshipable_id' => $this->source->id, 'related_model_id' => $this->target->id, 'scope' => 'within-tenant', 'bidirectional' => true],
        ['relationship_id' => $relationship('further-approval'), 'relationshipable_type' => 'institution_type', 'relationshipable_id' => (string) $this->senate->id, 'related_model_id' => (string) $this->council->id, 'scope' => 'cross-tenant', 'bidirectional' => false],
        ['relationship_id' => $relationship('something-custom'), 'relationshipable_type' => 'institution_type', 'relationshipable_id' => (string) $this->council->id, 'related_model_id' => (string) $this->commission->id, 'scope' => 'within-tenant', 'bidirectional' => false],
    ]);

    Permission::findOrCreate('relationshipables.update.*', 'web');
    Permission::findOrCreate('relationships.update.padalinys', 'web');
    Permission::findOrCreate('relationships.update.*', 'web');
});

test('converts links, keeping cross-tenant access in both orientations and sibling flags as self-links', function (): void {
    institutionLinksMigration()->up();

    expect(DB::table('institution_links')->get(['source_institution_id', 'target_institution_id', 'kind', 'mutual'])->map(fn ($row) => (array) $row)->all())
        ->toBe([['source_institution_id' => $this->source->id, 'target_institution_id' => $this->target->id, 'kind' => 'approves_composition', 'mutual' => 1]]);

    $typeLinks = DB::table('institution_type_links')->get()
        ->map(fn ($row) => "{$row->source_type_id}>{$row->target_type_id} {$row->kind} mutual={$row->mutual} cross={$row->cross_tenant}")
        ->sort()->values()->all();

    expect($typeLinks)->toBe(collect([
        "{$this->senate->id}>{$this->council->id} forwards_issues mutual=0 cross=1",
        "{$this->council->id}>{$this->senate->id} forwards_issues mutual=0 cross=1",
        "{$this->council->id}>{$this->commission->id} related mutual=0 cross=0",
        "{$this->senate->id}>{$this->senate->id} related mutual=1 cross=0",
        "{$this->commission->id}>{$this->commission->id} related mutual=0 cross=1",
    ])->sort()->values()->all())
        ->and($this->senate->fresh()->extra_attributes)->toBe(['governance_scope' => 'vu'])
        ->and($this->commission->fresh()->extra_attributes)->toBeNull();
});

test('drops the legacy tables and the permissions nothing can hold any more', function (): void {
    institutionLinksMigration()->up();

    expect(Schema::hasTable('relationshipables'))->toBeFalse()
        ->and(Schema::hasTable('relationships'))->toBeFalse()
        ->and(Permission::query()->whereIn('name', ['relationshipables.update.*', 'relationships.update.padalinys'])->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'relationships.update.*')->exists())->toBeTrue();
});

test('rolling back restores the sibling flags', function (): void {
    $migration = institutionLinksMigration();
    $migration->up();
    $migration->down();

    expect($this->senate->fresh()->extra_attributes)->toMatchArray(['enable_sibling_relationships' => true])
        ->and($this->commission->fresh()->extra_attributes)->toMatchArray(['enable_cross_tenant_sibling_relationships' => true])
        ->and(DB::table('relationshipables')->where('relationshipable_type', 'institution')->value('bidirectional'))->toBe(1);
});
