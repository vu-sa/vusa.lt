<?php

use App\Actions\ProjectDutyResponsibilities;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

/** Transitional(responsibility-grants): delete with the migration once it has run on production. */
function responsibilityGrantsBackfill(): object
{
    return require base_path('database/migrations/2026_10_19_210100_backfill_responsibility_grants.php');
}

/**
 * @return list<string>
 */
function permissionsOf(Duty $duty): array
{
    return DB::table('model_has_roles')
        ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
        ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
        ->where('model_has_roles.model_id', $duty->id)
        ->distinct()
        ->orderBy('permissions.name')
        ->pluck('permissions.name')
        ->all();
}

beforeEach(function (): void {
    // The test seeders already speak grants; rewind to the shape production has before this migration.
    DB::table('responsibility_grants')->delete();
    DB::table('roles')->update(['purpose' => null, 'title' => null]);

    $this->tenant = Tenant::query()->firstOrFail();
    $institution = Institution::factory()->for($this->tenant)->create();
    $this->representative = Role::query()->where('name', 'Studentų atstovas')->firstOrFail();
    $this->registration = Role::factory()->create(['title' => null]);
    $this->registration->givePermissionTo('forms.read.padalinys');
    $this->coordinator = Role::query()->where('name', 'Studentų atstovų koordinatorius')->firstOrFail();
    $this->type = DutyType::factory()->create();

    $this->typedRep = Duty::factory()->for($institution)->create();
    $this->handPickedRep = Duty::factory()->for($institution)->create();
    $this->intake = Duty::factory()->for($institution)->create();
    $this->coordination = Duty::factory()->for($institution)->create();

    DB::table('duty_duty_type')->insert(['duty_id' => $this->typedRep->id, 'duty_type_id' => $this->type->id]);
    DB::table('duty_type_role')->insert(['role_id' => $this->representative->id, 'duty_type_id' => $this->type->id]);
    DB::table('model_has_roles')->insert([
        ['role_id' => $this->representative->id, 'model_type' => 'duty', 'model_id' => $this->typedRep->id],
        ['role_id' => $this->representative->id, 'model_type' => 'duty', 'model_id' => $this->handPickedRep->id],
        ['role_id' => $this->registration->id, 'model_type' => 'duty', 'model_id' => $this->intake->id],
        ['role_id' => $this->coordinator->id, 'model_type' => 'duty', 'model_id' => $this->coordination->id],
    ]);
    DB::table('duty_responsibilities')->insert([
        'id' => strtolower((string) Str::ulid()),
        'duty_id' => $this->coordination->id,
        'responsibility' => 'student_rep_coordination',
        'scope_type' => 'tenant',
        'scope_id' => (string) $this->tenant->id,
    ]);
    DB::table('settings')->updateOrInsert(
        ['group' => 'forms', 'name' => 'member_registration_notification_recipient_role_id'],
        ['payload' => json_encode($this->registration->id), 'updated_at' => now()],
    );
});

test('it keeps every duty\'s permissions and gives purposes only to the people already in the group', function (): void {
    $duties = [$this->typedRep, $this->handPickedRep, $this->intake, $this->coordination];
    $before = collect($duties)->mapWithKeys(fn (Duty $duty) => [$duty->id => permissionsOf($duty)]);

    responsibilityGrantsBackfill()->up();

    foreach ($duties as $duty) {
        expect(permissionsOf($duty))->toBe($before[$duty->id]);
    }

    $access = Role::query()->where('name', 'studentu-atstovo-prieiga')->firstOrFail();
    $coordination = Role::forPurpose(ResponsibilityPurpose::StudentRepCoordination);

    expect($this->representative->fresh()->purpose)->toBe(ResponsibilityPurpose::StudentRepresentation)
        ->and($this->type->roles()->pluck('roles.id')->all())->toBe([$this->representative->id])
        ->and($this->handPickedRep->roles()->pluck('roles.id')->all())->toBe([$access->id])
        ->and($access->purpose)->toBeNull()
        ->and($this->registration->fresh()->purpose)->toBe(ResponsibilityPurpose::MemberRegistration)
        ->and($this->intake->responsibilityGrants()->sole()->only(['scope_type', 'scope_id']))->toBe(['scope_type' => 'tenant', 'scope_id' => (string) $this->tenant->id])
        ->and($coordination->permissions)->toBeEmpty()
        ->and($this->coordination->roles()->pluck('roles.id')->all())->toEqualCanonicalizing([$this->coordinator->id, $coordination->id])
        ->and($this->representative->fresh()->getTranslation('title', 'lt'))->toBe('Studentų atstovas')
        ->and(ProjectDutyResponsibilities::drift())->toBe([]);
});

test('down returns the hand-picked representatives to the representative role', function (): void {
    $migration = responsibilityGrantsBackfill();
    $migration->up();
    $migration->down();

    expect(ResponsibilityGrant::query()->count())->toBe(0)
        ->and(Role::query()->where('name', 'studentu-atstovo-prieiga')->exists())->toBeFalse()
        ->and($this->handPickedRep->roles()->pluck('roles.id')->all())->toBe([$this->representative->id]);
});
