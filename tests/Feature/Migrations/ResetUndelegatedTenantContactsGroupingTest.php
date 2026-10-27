<?php

use App\Models\Duty;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('resets grouping by unit on duties no other unit may assign to', function (): void {
    $undelegated = Duty::factory()->create(['contacts_grouping' => 'tenant', 'updated_at' => '2026-01-01 10:00:00']);
    $delegated = Duty::factory()->create(['contacts_grouping' => 'tenant']);
    $delegated->assignableTenants()->attach(Tenant::query()->first());
    $byProgram = Duty::factory()->create(['contacts_grouping' => 'study_program']);

    (require base_path('database/migrations/2026_10_27_130000_reset_undelegated_tenant_contacts_grouping.php'))->up();

    expect($undelegated->fresh()->contacts_grouping)->toBe('none')
        ->and($undelegated->fresh()->updated_at->toDateTimeString())->toBe('2026-01-01 10:00:00')
        ->and($delegated->fresh()->contacts_grouping)->toBe('tenant')
        ->and($byProgram->fresh()->contacts_grouping)->toBe('study_program');
});
