<?php

use App\Actions\MergeDuties;
use App\Actions\ProjectDutyResponsibilities;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
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

describe('duty type tree', function (): void {
    beforeEach(function (): void {
        $this->parent = DutyType::factory()->create();
        $this->child = DutyType::factory()->create(['parent_id' => $this->parent->id]);
        $this->duty->types()->attach($this->child);
    });

    test('a duty receives what its type\'s ancestors are given, but a parent does not receive its subtype\'s grants', function (): void {
        $parentDuty = Duty::factory()->for($this->institution)->create();
        $parentDuty->types()->attach($this->parent);
        $childOnly = Role::factory()->create();

        ResponsibilityGrant::factory()->forDutyType($this->parent)->of($this->role)->create();
        ResponsibilityGrant::factory()->forDutyType($this->child)->of($childOnly)->create();

        expect(roleIdsOf($this->duty))->toEqualCanonicalizing([$this->role->id, $childOnly->id])
            ->and(roleIdsOf($parentDuty))->toBe([$this->role->id]);
    });

    test('permissions granted to a subtype do not reach holders of its parent or grandparent type', function (): void {
        $grandparent = DutyType::factory()->create();
        $this->parent->update(['parent_id' => $grandparent->id]);
        $this->role->givePermissionTo('news.delete.*');
        ResponsibilityGrant::factory()->forDutyType($this->child)->of($this->role)->create();

        $holderOf = function (DutyType $type): User {
            $duty = Duty::factory()->for($this->institution)->create();
            $duty->types()->attach($type);

            return User::factory()->hasAttached($duty, ['start_date' => now()->subDay()])->create();
        };
        $authorizer = app(ModelAuthorizer::class);

        expect($authorizer->allows($holderOf($this->child), 'news.delete.*'))->toBeTrue()
            ->and($authorizer->allows($holderOf($this->parent), 'news.delete.*'))->toBeFalse()
            ->and($authorizer->allows($holderOf($grandparent), 'news.delete.*'))->toBeFalse();
    });

    test('moving a subtype out from under its parent takes the inherited responsibilities away', function (): void {
        ResponsibilityGrant::factory()->forDutyType($this->parent)->of($this->role)->create();

        $this->child->update(['parent_id' => null]);

        expect(roleIdsOf($this->duty))->toBeEmpty();

        $this->child->update(['parent_id' => $this->parent->id]);

        expect(roleIdsOf($this->duty))->toBe([$this->role->id]);
    });

    test('a trashed parent stops passing its grants on until restored', function (): void {
        ResponsibilityGrant::factory()->forDutyType($this->parent)->of($this->role)->create();

        $this->parent->delete();

        expect(roleIdsOf($this->duty))->toBeEmpty();

        $this->parent->restore();

        expect(roleIdsOf($this->duty))->toBe([$this->role->id]);
    });

    test('the drift check agrees with the inherited projection', function (): void {
        ResponsibilityGrant::factory()->forDutyType($this->parent)->of($this->role)->create();

        expect(ProjectDutyResponsibilities::drift())->toBe([]);
    });
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
