<?php

use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

function responsibilityBackfillMigration(): object
{
    return require base_path('database/migrations/2026_09_28_090100_backfill_student_rep_coordination_responsibilities.php');
}

test('backfills each legacy role duty once and grants padalinys institution administration', function (): void {
    $role = Role::firstOrCreate(['name' => 'Responsibility Backfill Test', 'guard_name' => 'web']);
    $tenants = Tenant::query()->take(2)->get();
    $duties = $tenants->map(fn (Tenant $tenant) => Duty::factory()->for(Institution::factory()->for($tenant))->create());
    $duties->each(fn (Duty $duty) => $duty->assignRole($role));
    $unrelated = Duty::factory()->for(Institution::factory()->for($tenants->first()))->create();
    DutyResponsibility::factory()->for($duties->first())->forTenant($tenants->first())->create();

    DB::table('settings')->updateOrInsert(
        ['group' => 'atstovavimas', 'name' => 'institution_manager_role_id'],
        ['payload' => json_encode($role->id), 'updated_at' => now()],
    );

    $migration = responsibilityBackfillMigration();
    $migration->up();
    $migration->up();

    expect(DutyResponsibility::query()->whereIn('duty_id', $duties->pluck('id'))->count())->toBe(2)
        ->and($unrelated->responsibilities()->exists())->toBeFalse()
        ->and($role->fresh()->hasPermissionTo('institutions.update.padalinys'))->toBeTrue();

    $migration->down();

    expect(DutyResponsibility::query()->whereIn('duty_id', $duties->pluck('id'))->count())->toBe(2);

    (require base_path('database/settings/2026_09_28_090200_remove_institution_manager_role_from_atstovavimas_settings.php'))->up();

    expect(DB::table('settings')->where('group', 'atstovavimas')->where('name', 'institution_manager_role_id')->exists())->toBeFalse();
});
