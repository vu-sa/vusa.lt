<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
});

function userWithFirstTermStarting(Tenant $tenant, string $start): User
{
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $user = User::factory()->create();
    $user->duties()->attach($duty, ['start_date' => $start]);

    return $user;
}

describe('access-change band (U14)', function (): void {
    test('a term that began today is announced', function (): void {
        $user = userWithFirstTermStarting($this->tenant, now()->toDateString());

        asUser($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->has('accessChanges', 1)
            ->where('accessChanges.0.kind', 'started')
        );
    });

    test('a term whose last day was yesterday has ended', function (): void {
        $user = userWithFirstTermStarting($this->tenant, now()->subYear()->toDateString());
        $user->dutiables()->update(['end_date' => now()->subDay()->toDateString()]);

        asUser($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->has('accessChanges', 1)
            ->where('accessChanges.0.kind', 'ended')
        );
    });

    test('an old change is history, not a band', function (): void {
        $user = userWithFirstTermStarting($this->tenant, now()->subMonths(3)->toDateString());

        asUser($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->has('accessChanges', 0));
    });
});

describe('launching the action window from the URL (U21)', function (): void {
    beforeEach(function (): void {
        // A rep may open their own institution; that is the whole point of the reminder's link.
        $this->user = makeTenantUserWithRole('Student Representative', $this->tenant);
        $this->institution = $this->user->current_duties()->first()->institution;
    });

    test('a reminder link opens the flow with the institution filled in', function (string $window): void {
        asUser($this->user)
            ->get(route('dashboard', ['window' => $window, 'institution' => $this->institution->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('actionWindowLaunch.flow', $window)
                ->where('actionWindowLaunch.institution.id', (string) $this->institution->id)
                ->where('actionWindowLaunch.institution.name', $this->institution->name)
            );
    })->with(['meeting.create', 'check-in']);

    test('anything it cannot honour is ignored rather than an error', function (array $query): void {
        asUser($this->user)
            ->get(route('dashboard', $query))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('actionWindowLaunch', null));
    })->with([
        'a window it does not know' => [fn () => ['window' => 'users.delete', 'institution' => 'x']],
        'an institution that does not exist' => [fn () => ['window' => 'check-in', 'institution' => '01hzzzzzzzzzzzzzzzzzzzzzzz']],
        'no institution at all' => [fn () => ['window' => 'check-in']],
    ]);

    test('an institution the user may not view opens nothing', function (): void {
        $foreign = Institution::factory()->for(Tenant::factory()->create())->create();

        asUser($this->user)
            ->get(route('dashboard', ['window' => 'meeting.create', 'institution' => $foreign->id]))
            ->assertInertia(fn (Assert $page) => $page->where('actionWindowLaunch', null));
    });
});
