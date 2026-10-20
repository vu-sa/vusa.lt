<?php

use App\Actions\MergeDuties;
use App\Actions\ProjectDutyResponsibilities;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->firstOrFail();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create();
    $this->role = Role::factory()->create();
});

function roleIdsOf(Duty $duty): array
{
    return $duty->roles()->pluck('roles.id')->all();
}

test('a duty receives responsibilities granted to it, to its types and to its institution', function (): void {
    $type = DutyType::factory()->create();
    $this->duty->types()->attach($type);
    [$viaType, $viaInstitution] = Role::factory()->count(2)->create();

    ResponsibilityGrant::factory()->forDuty($this->duty)->of($this->role)->create();
    ResponsibilityGrant::factory()->forDutyType($type)->of($viaType)->create();
    ResponsibilityGrant::factory()->forWholeInstitution($this->institution)->of($viaInstitution)->create();

    expect(roleIdsOf($this->duty))->toEqualCanonicalizing([$this->role->id, $viaType->id, $viaInstitution->id]);
});

test('unlinking a type keeps the same responsibility granted directly', function (): void {
    $type = DutyType::factory()->create();
    $this->duty->types()->attach($type);
    ResponsibilityGrant::factory()->forDutyType($type)->of($this->role)->create();
    ResponsibilityGrant::factory()->forDuty($this->duty)->of($this->role)->create();

    $this->duty->types()->detach($type);

    expect(roleIdsOf($this->duty))->toBe([$this->role->id]);

    $this->duty->responsibilityGrants()->get()->each->delete();

    expect(roleIdsOf($this->duty))->toBeEmpty();
});

test('an institution grant follows a duty that moves to another institution', function (): void {
    ResponsibilityGrant::factory()->forWholeInstitution($this->institution)->of($this->role)->create();

    $this->duty->update(['institution_id' => Institution::factory()->for($this->tenant)->create()->id]);

    expect(roleIdsOf($this->duty))->toBeEmpty();

    $newcomer = Duty::factory()->for($this->institution)->create();

    expect(roleIdsOf($newcomer))->toBe([$this->role->id]);
});

test('the role api on a duty writes direct grants that survive re-projection', function (): void {
    $this->duty->assignRole($this->role);
    ProjectDutyResponsibilities::forDuties([$this->duty->id]);

    expect($this->duty->responsibilityGrants()->pluck('role_id')->all())->toBe([$this->role->id])
        ->and(roleIdsOf($this->duty))->toBe([$this->role->id]);

    $this->duty->removeRole($this->role);

    expect(roleIdsOf($this->duty))->toBeEmpty();
});

test('merging duties moves their grants to the kept duty', function (): void {
    $source = Duty::factory()->for($this->institution)->create();
    ResponsibilityGrant::factory()->forDuty($source)->of($this->role)->create();

    MergeDuties::execute($this->duty, collect([$source]));

    expect(roleIdsOf($this->duty))->toBe([$this->role->id]);
});

test('the command reports a drifted duty and rebuilds it', function (): void {
    ResponsibilityGrant::factory()->forDuty($this->duty)->of($this->role)->create();
    DB::table('model_has_roles')->where('model_id', $this->duty->id)->delete();

    expect(Artisan::call('responsibilities:project', ['--check' => true]))->toBe(1)
        ->and(roleIdsOf($this->duty))->toBeEmpty()
        ->and(Artisan::call('responsibilities:project'))->toBe(0)
        ->and(roleIdsOf($this->duty))->toBe([$this->role->id])
        ->and(ProjectDutyResponsibilities::drift())->toBeEmpty();
});

test('a trashed duty stops inheriting from its type and gets it back when restored', function (): void {
    $type = DutyType::factory()->create();
    $this->duty->types()->attach($type);
    ResponsibilityGrant::factory()->forDutyType($type)->of($this->role)->create();

    $this->duty->delete();

    expect(roleIdsOf($this->duty))->toBeEmpty();

    $this->duty->restore();

    expect(roleIdsOf($this->duty))->toBe([$this->role->id]);
});
