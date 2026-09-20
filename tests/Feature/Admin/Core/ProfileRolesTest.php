<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
});

function attachTerm($user, Tenant $tenant, array $pivot): Duty
{
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $user->duties()->attach($duty, $pivot);

    return $duty;
}

test('a guest is sent to login', function (): void {
    $this->get(route('profile.roles'))->assertRedirect();
});

test('the page lists the acting user\'s current duty with its institution', function (): void {
    $duty = $this->user->current_duties()->first();

    asUser($this->user)
        ->get(route('profile.roles'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowMyRoles')
            ->has('access.current', 1)
            ->where('access.current.0.dutyName', $duty->name)
            ->where('access.current.0.endDate', null)
            ->where('access.current.0.isExOfficio', false)
            ->has('access.upcoming', 0)
            ->has('access.ended', 0)
        );
});

test('terms are split into current, upcoming and ended by their dates', function (): void {
    attachTerm($this->user, $this->tenant, ['start_date' => now()->addDays(5)->toDateString()]);
    attachTerm($this->user, $this->tenant, [
        'start_date' => now()->subYear()->toDateString(),
        'end_date' => now()->subDays(10)->toDateString(),
    ]);

    asUser($this->user)
        ->get(route('profile.roles'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('access.current', 1)
            ->has('access.upcoming', 1)
            ->has('access.ended', 1)
        );
});

test('a term that ends today is still current', function (): void {
    attachTerm($this->user, $this->tenant, [
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->toDateString(),
    ]);

    asUser($this->user)
        ->get(route('profile.roles'))
        ->assertInertia(fn (Assert $page) => $page->has('access.current', 2)->has('access.ended', 0));
});

test('roles come from the duty and from direct assignment', function (): void {
    $this->user->assignRole('Communication Coordinator');
    $this->user->current_duties()->first()->assignRole('Išteklių administratorius');

    asUser($this->user)
        ->get(route('profile.roles'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('access.directRoles', ['Communication Coordinator'])
            ->where('access.current.0.roles', ['Išteklių administratorius'])
        );
});

test('a derived ex-officio seat and a cross-tenant representative are marked', function (): void {
    $baseTerm = $this->user->dutiables()->first();
    $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();

    attachTerm($this->user, $this->tenant, [
        'start_date' => now()->subDay()->toDateString(),
        'via_dutiable_id' => $baseTerm->id,
    ]);
    attachTerm($this->user, $this->tenant, [
        'start_date' => now()->subDay()->toDateString(),
        'tenant_id' => $otherTenant->id,
    ]);

    asUser($this->user)
        ->get(route('profile.roles'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('access.current', fn ($terms) => collect($terms)->contains('isExOfficio', true)
                && collect($terms)->contains('representsTenant', $otherTenant->shortname))
        );
});

test('another user\'s duties never appear', function (): void {
    $stranger = makeUser($this->tenant);
    attachTerm($stranger, $this->tenant, ['start_date' => now()->subDay()->toDateString()]);

    asUser($this->user)
        ->get(route('profile.roles'))
        ->assertInertia(fn (Assert $page) => $page->has('access.current', 1));
});

test('a super admin is told so', function (): void {
    $admin = makeAdminUser($this->tenant);

    asUser($admin)
        ->get(route('profile.roles'))
        ->assertInertia(fn (Assert $page) => $page->where('access.isSuperAdmin', true));
});

test('a link is offered only for records the user may open', function (): void {
    asUser($this->user)
        ->get(route('profile.roles'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('access.current.0.dutyHref', null)
            ->where('access.current.0.institutionHref', null)
        );
});
