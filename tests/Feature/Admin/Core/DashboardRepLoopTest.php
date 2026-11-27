<?php

use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
});

describe('launching the action window from the URL (U21)', function (): void {
    beforeEach(function (): void {
        // A rep may open their own institution; that is the whole point of the reminder's link.
        $this->user = makeTenantUserWithRole('Studentų atstovas', $this->tenant);
        $this->institution = $this->user->current_duties()->first()->institution;
    });

    test('a reminder link opens the flow with the institution filled in', function (string $window): void {
        asUserWithInertia($this->user)
            ->get(route('dashboard', ['window' => $window, 'institution' => $this->institution->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('actionWindowLaunch.flow', $window)
                ->where('actionWindowLaunch.institution.id', (string) $this->institution->id)
                ->where('actionWindowLaunch.institution.name', $this->institution->name)
            );
    })->with(['meeting.create', 'check-in']);

    test('anything it cannot honour is ignored rather than an error', function (array $query): void {
        asUserWithInertia($this->user)
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

        asUserWithInertia($this->user)
            ->get(route('dashboard', ['window' => 'meeting.create', 'institution' => $foreign->id]))
            ->assertInertia(fn (Assert $page) => $page->where('actionWindowLaunch', null));
    });
});
