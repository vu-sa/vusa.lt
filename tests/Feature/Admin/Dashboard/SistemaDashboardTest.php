<?php

use App\Enums\SupportRequestStatus;
use App\Models\SupportRequest;
use App\Models\Tenant;
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
