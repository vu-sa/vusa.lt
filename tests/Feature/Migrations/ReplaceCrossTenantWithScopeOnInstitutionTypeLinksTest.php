<?php

use App\Models\InstitutionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('cross-tenant type links become main-to-units, the rest same-tenant', function (): void {
    $migration = require base_path('database/migrations/2026_10_18_183729_replace_cross_tenant_with_scope_on_institution_type_links.php');
    $migration->down();

    $type = InstitutionType::factory()->create();
    DB::table('institution_type_links')->insert([
        ['source_type_id' => $type->id, 'target_type_id' => $type->id, 'kind' => 'related', 'mutual' => false, 'cross_tenant' => true],
        ['source_type_id' => $type->id, 'target_type_id' => $type->id, 'kind' => 'related', 'mutual' => true, 'cross_tenant' => false],
    ]);

    $migration->up();

    expect(DB::table('institution_type_links')->orderBy('id')->pluck('scope')->all())->toBe(['main_to_units', 'same_tenant']);
});
