<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = makeTenantUserWithRole('Komunikacijos koordinatorius', Tenant::query()->first());

    DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'notifiable_type' => $this->user->getMorphClass(),
        'notifiable_id' => $this->user->getKey(),
        'data' => ['text' => 'Sveikas'],
    ]);
});

test('a guest page does not query users', function (): void {
    DB::enableQueryLog();

    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))->assertOk();

    $userQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query) => str_contains($query['query'], 'from "users"'));

    expect($userQueries)->toBeEmpty();
});

test('public pages leave out admin-only shared data', function (): void {
    asUser($this->user)
        ->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.id', $this->user->id)
            ->where('auth.can.index', [])
            ->where('auth.can.create', [])
            ->where('auth.user.unreadNotifications', [])
            ->where('auth.user.tenants', [])
            ->where('tags', [])
            ->where('eventTypes', [])
            ->where('pwa.subscriptionEndpoints', [])
            ->missing('auth.user.roles')
            ->missing('auth.user.current_duties')
            ->missing('auth.can.manageSettings')
        );
});

test('admin pages share the data the admin shell needs', function (): void {
    asUser($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can.index.news', true)
            ->has('auth.user.unreadNotifications', 1)
            ->has('auth.user.tenants', 1)
            ->missing('auth.user.roles')
        );
});
