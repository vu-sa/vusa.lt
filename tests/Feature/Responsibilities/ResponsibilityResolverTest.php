<?php

use App\Enums\InstitutionScope;
use App\Enums\Responsibility;
use App\Enums\ResponsibilityScope;
use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ResponsibilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
});

function resolver(): ResponsibilityResolver
{
    return app(ResponsibilityResolver::class);
}

/** A duty with one current holder that coordinates the given scope. */
function coordinatorDuty(string $factoryState, mixed $scope, ?string $holderName = null): Duty
{
    $duty = Duty::factory()->for(Institution::factory()->for(Tenant::query()->first()))->create();
    DutyResponsibility::factory()->for($duty)->{$factoryState}($scope)->create();
    User::factory()->create(['name' => $holderName ?? fake()->name()])
        ->duties()->attach($duty, ['start_date' => now()->subMonth()]);

    return $duty;
}

describe('dutiesFor', function (): void {
    test('finds the padalinys coordinator for an institution with no closer assignment', function (): void {
        $duty = coordinatorDuty('forTenant', $this->tenant);

        expect(resolver()->dutiesFor(Responsibility::StudentRepCoordination, $this->institution)->pluck('id')->all())->toBe([$duty->id])
            ->and(resolver()->sourceFor(Responsibility::StudentRepCoordination, $this->institution))->toBe(ResponsibilityScope::Tenant);
    });

    test('lets a type assignment override the padalinys, and an institution assignment override both', function (): void {
        $type = InstitutionType::factory()->withGovernanceScope()->create();
        $this->institution->types()->attach($type);
        coordinatorDuty('forTenant', $this->tenant);
        $typeDuty = coordinatorDuty('forType', $type);

        expect(resolver()->dutiesFor(Responsibility::StudentRepCoordination, $this->institution->fresh())->pluck('id')->all())->toBe([$typeDuty->id]);

        $institutionDuty = coordinatorDuty('forInstitution', $this->institution);
        resolver()->flush();

        expect(resolver()->dutiesFor(Responsibility::StudentRepCoordination, $this->institution->fresh())->pluck('id')->all())->toBe([$institutionDuty->id])
            ->and(resolver()->sourceFor(Responsibility::StudentRepCoordination, $this->institution->fresh()))->toBe(ResponsibilityScope::Institution);
    });

    test('reaches an institution through an ancestor of its type, but a closer type wins', function (): void {
        $parent = InstitutionType::factory()->withGovernanceScope()->create();
        $child = InstitutionType::factory()->withGovernanceScope()->create(['parent_id' => $parent->id]);
        $this->institution->types()->attach($child);
        $parentDuty = coordinatorDuty('forType', $parent);

        expect(resolver()->dutiesFor(Responsibility::StudentRepCoordination, $this->institution->fresh())->pluck('id')->all())->toBe([$parentDuty->id]);

        $childDuty = coordinatorDuty('forType', $child);
        resolver()->flush();

        expect(resolver()->dutiesFor(Responsibility::StudentRepCoordination, $this->institution->fresh())->pluck('id')->all())->toBe([$childDuty->id]);
    });

    test('names nobody for a body that is not a VU body', function (): void {
        $this->institution->types()->attach(InstitutionType::factory()->withGovernanceScope(InstitutionScope::Vusa)->create());
        coordinatorDuty('forTenant', $this->tenant);

        expect(resolver()->dutiesFor(Responsibility::StudentRepCoordination, $this->institution->fresh()))->toBeEmpty();
    });
});

describe('usersFor', function (): void {
    test('counts only people whose term in the responsible duty is current', function (): void {
        $duty = coordinatorDuty('forTenant', $this->tenant, 'Dabartinė');
        User::factory()->create(['name' => 'Buvusi'])
            ->duties()->attach($duty, ['start_date' => now()->subYear(), 'end_date' => now()->subDay()]);

        expect(resolver()->usersFor(Responsibility::StudentRepCoordination, $this->institution)->pluck('name')->all())->toBe(['Dabartinė']);
    });
});

describe('institutionIdsFor', function (): void {
    test('covers the padalinys except an institution assigned to someone else', function (): void {
        $senate = Institution::factory()->for($this->tenant)->create();
        $duty = coordinatorDuty('forTenant', $this->tenant);
        coordinatorDuty('forInstitution', $senate);
        $holder = $duty->current_users()->first();

        $ids = resolver()->institutionIdsFor($holder, Responsibility::StudentRepCoordination);

        expect($ids)->toContain((string) $this->institution->id)
            ->and($ids)->not->toContain((string) $senate->id);
    });

    test('uses bounded queries and respects a closer type assignment', function (): void {
        $type = InstitutionType::factory()->withGovernanceScope()->create();
        $typeInstitution = Institution::factory()->for($this->tenant)->create();
        $typeInstitution->types()->attach($type);
        Institution::factory()->for($this->tenant)->count(12)->create();
        $tenantDuty = coordinatorDuty('forTenant', $this->tenant);
        coordinatorDuty('forType', $type);
        $holder = $tenantDuty->current_users()->firstOrFail();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $ids = resolver()->institutionIdsFor($holder, Responsibility::StudentRepCoordination);
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        expect($ids)->toContain((string) $this->institution->id)
            ->not->toContain((string) $typeInstitution->id)
            ->and($queryCount)->toBeLessThan(12);
    });
});

describe('gaps', function (): void {
    test('lists padaliniai with nobody currently coordinating them', function (): void {
        $covered = $this->tenant;
        coordinatorDuty('forTenant', $covered);
        $uncovered = Tenant::query()->representational()->whereKeyNot($covered->id)->firstOrFail();

        $gaps = resolver()->gaps(Responsibility::StudentRepCoordination)->pluck('id');

        expect($gaps)->not->toContain($covered->id)
            ->and($gaps)->toContain($uncovered->id);
    });
});
