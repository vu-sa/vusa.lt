<?php

use App\Enums\SupportRequestStatus;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeAdminUser($this->tenant);
    $this->member = makeUser($this->tenant);
});

describe('access', function (): void {
    test('a member with no system sections is refused', function (): void {
        asUser($this->member)->get(route('dashboard.sistema'))->assertStatus(403);
    });

    test('a coordinator without system access is refused', function (): void {
        $coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

        asUser($coordinator)->get(route('dashboard.sistema'))->assertStatus(403);
    });

    test('a super admin opens it', function (): void {
        asUser($this->admin)->get(route('dashboard.sistema'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowSistema')
                ->has('counts')
                ->has('newRequests')
            );
    });

    test('guests are redirected', function (): void {
        $this->get(route('dashboard.sistema'))->assertRedirect();
    });
});

describe('what it counts', function (): void {
    test('the scheduled member filter only sees duties in the reader’s tenant', function (): void {
        $coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
        $otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
        $localDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $otherDuty = Duty::factory()->for(Institution::factory()->for($otherTenant))->create();
        $localHolder = User::factory()->create();
        $otherHolder = User::factory()->create();

        Dutiable::factory()->forDuty($localDuty)->forUser($localHolder)->create([
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => null,
        ]);
        Dutiable::factory()->forDuty($otherDuty)->forUser($otherHolder)->create([
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => null,
        ]);
        Dutiable::factory()->forDuty($localDuty)->forUser($otherHolder)->create([
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->subMonth()->toDateString(),
        ]);

        asUser($coordinator)->get(route('api.v1.admin.users.index', ['future_duty' => 'scheduled']))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $localHolder->id);
    });

    test('future duty holders are counted once and the linked member list shows those people', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $firstDuty = Duty::factory()->for($institution)->create();
        $secondDuty = Duty::factory()->for($institution)->create();
        $scheduled = User::factory()->create();
        $current = User::factory()->create();

        foreach ([$firstDuty, $secondDuty] as $duty) {
            Dutiable::factory()->forDuty($duty)->forUser($scheduled)->create([
                'start_date' => now()->addMonth()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
            ]);
        }

        Dutiable::factory()->forDuty($firstDuty)->forUser($current)->create([
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        asUser($this->admin)->get(route('dashboard.sistema'))
            ->assertInertia(fn (Assert $page) => $page->where('counts.futureDutyHolders', 1));

        asUser($this->admin)->get(route('users.index', ['future_duty' => 'scheduled']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.meta.total', 1)
                ->where('users.data.0.id', $scheduled->id)
            );

        asUser($this->admin)->get(route('api.v1.admin.users.index', [
            'future_duty' => 'scheduled',
            'filters' => json_encode(['future_duty' => 'scheduled']),
            'include_facets' => 'true',
            'facet_values' => json_encode(['future_duty' => ['scheduled']]),
            'facet_single' => json_encode(['future_duty']),
        ]))->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $scheduled->id)
            ->assertJsonPath('data.facets.future_duty.scheduled', 1);

        asUser($this->admin)->get(route('users.show', $scheduled))
            ->assertInertia(fn (Assert $page) => $page->where('user.upcoming_duties.0.id', $firstDuty->id));
    });

    test('open support requests exclude the finished ones, and new ones are listed', function (): void {
        SupportRequest::factory()->create(['status' => SupportRequestStatus::New, 'title' => 'Neveikia prisijungimas']);
        SupportRequest::factory()->create(['status' => SupportRequestStatus::Done]);
        SupportRequest::factory()->create(['status' => SupportRequestStatus::Declined]);

        asUser($this->admin)->get(route('dashboard.sistema'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.openRequests', fn ($open) => $open >= 1)
                ->has('newRequests', 1)
                ->where('newRequests.0.title', 'Neveikia prisijungimas')
            );
    });

    test('the system check is deferred so the monitor never blocks the first paint', function (): void {
        asUser($this->admin)->get(route('dashboard.sistema'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('problems')
                ->loadDeferredProps('secondary', fn (Assert $deferred) => $deferred->has('problems'))
            );
    });
});
