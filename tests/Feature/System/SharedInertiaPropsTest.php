<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Tenant;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
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

test('the unread count covers notifications beyond the shared list', function (): void {
    foreach (range(1, HandleInertiaRequests::NOTIFICATION_PREVIEW_LIMIT) as $index) {
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'notifiable_type' => $this->user->getMorphClass(),
            'notifiable_id' => $this->user->getKey(),
            'data' => ['text' => "Pranešimas {$index}"],
        ]);
    }

    asUser($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('auth.user.unreadNotifications', HandleInertiaRequests::NOTIFICATION_PREVIEW_LIMIT)
            ->where('auth.user.unreadNotificationsCount', HandleInertiaRequests::NOTIFICATION_PREVIEW_LIMIT + 1)
        );
});

test('admin pages share both pronoun translations for personalized labels', function (): void {
    $this->user->update(['pronouns' => ['lt' => '', 'en' => 'she / her']]);

    $this->actingAs($this->user)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.pronouns', ['en' => 'she / her'])
        );
});

test('JSON visits support Inertia assertions and ordinary visits clear their headers', function (): void {
    asUserWithInertia($this->user)->get(route('dashboard'))->assertOk()
        ->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'Admin/ShowAdminHome')
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.id', $this->user->id));

    asUser($this->user)->get(route('dashboard'))->assertOk()->assertHeaderMissing('X-Inertia');
});

test('once configuration is omitted on subsequent visits while locale data stays fresh', function (): void {
    config(['services.carto.api_key' => 'test-map-key']);
    $first = asUserWithInertia($this->user)->get(route('dashboard'))->assertOk();
    $first->assertJsonPath('props.map.cartoApiKey', 'test-map-key');
    $this->withHeader('X-Inertia-Except-Once-Props', 'map')->get(route('dashboard'))->assertOk()
        ->assertJsonMissingPath('props.map')->assertJsonStructure(['props' => ['organization', 'tenants', 'typesenseConfig', 'ziggyContext']]);
});

test('permission map invalidation refreshes the combined payload', function (): void {
    asUserWithInertia($this->user)->get(route('dashboard'))->assertOk();
    $key = PermissionMapBuilder::ALL_CACHE_PREFIX.$this->user->id;
    expect(Cache::get($key))->toHaveKeys(['index', 'create', 'forceDelete']);

    PermissionMapBuilder::forgetCachedMaps($this->user->id);
    expect(Cache::has($key))->toBeFalse();
    asUserWithInertia($this->user)->get(route('dashboard'))->assertOk()
        ->assertJsonPath('props.auth.can.index.news', true);
});

test('the combined permission cache expires when the last active duty day ends', function (): void {
    $this->travelTo('2026-10-20 23:59:00');
    $this->user->duties()->updateExistingPivot($this->user->duties()->first()->id, ['end_date' => today()->toDateString()]);
    asUserWithInertia($this->user)->get(route('dashboard'))->assertOk();
    $key = PermissionMapBuilder::ALL_CACHE_PREFIX.$this->user->id;
    expect(Cache::has($key))->toBeTrue();

    $this->travel(2)->minutes();
    expect(Cache::has($key))->toBeFalse();
});

test('route context follows locale defaults without serializing the route table', function (): void {
    $url = route('home', ['subdomain' => 'www', 'lang' => 'en']);
    asUserWithInertia($this->user)->get($url)->assertOk()
        ->assertJsonPath('props.ziggyContext.defaults.newsString', 'news')
        ->assertJsonMissingPath('props.ziggyContext.routes');
});
