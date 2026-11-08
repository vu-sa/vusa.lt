<?php

use App\Enums\TenantType;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Tag;
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

test('admin pages omit public head defaults while public pages retain them', function (): void {
    asUserWithInertia($this->user)->get(route('dashboard'))->assertOk()
        ->assertJsonPath('props.head', []);

    $public = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))->assertOk();

    expect(implode('', $public->json('props.head')))
        ->toContain('og:site_name', 'twitter:card', 'name="description"');
});

test('notification previews contain only bell fields and retain legacy display data', function (): void {
    $notification = $this->user->unreadNotifications()->first();
    $notification->update(['data' => [
        'category' => 'comment',
        'text' => 'Sveikas',
        'excerpt' => 'Komentaras',
        'subject' => ['name' => 'Vardas', 'image' => '/avatar.jpg', 'id' => 'private-id'],
        'object' => ['modelClass' => 'Meeting', 'name' => 'Posėdis', 'url' => '/mano/meetings/1', 'id' => 'private-id'],
        'actions' => [['label' => 'Atverti', 'url' => '/mano/meetings/1'], ['label' => 'Kita', 'url' => '/other']],
        'context' => [['label' => 'Nereikalinga', 'value' => 'Papildoma']],
        'internal' => 'not rendered',
    ]]);
    DB::enableQueryLog();

    asUserWithInertia($this->user)->get(route('dashboard'))->assertOk()
        ->assertJsonCount(1, 'props.auth.user.unreadNotifications')
        ->assertJsonPath('props.auth.user.unreadNotificationsCount', 1)
        ->assertJsonPath('props.auth.user.unreadNotifications.0.data', [
            'category' => 'comment',
            'text' => 'Sveikas',
            'excerpt' => 'Komentaras',
            'subject' => ['name' => 'Vardas', 'image' => '/avatar.jpg'],
            'object' => ['modelClass' => 'Meeting', 'name' => 'Posėdis', 'url' => '/mano/meetings/1'],
            'primaryAction' => ['label' => 'Atverti', 'url' => '/mano/meetings/1'],
        ])
        ->assertJsonMissingPath('props.auth.user.unreadNotifications.0.notifiable_id')
        ->assertJsonMissingPath('props.auth.user.unreadNotifications.0.notifiable_type')
        ->assertJsonMissingPath('props.auth.user.unreadNotifications.0.updated_at');

    $countQueries = collect(DB::getQueryLog())->filter(fn (array $query) => str_contains(strtolower($query['query']), 'count(')
        && str_contains($query['query'], 'notifications')
    );
    expect($countQueries)->toBeEmpty();
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

test('catalogs are sent once while request-specific configuration stays fresh', function (): void {
    config(['services.carto.api_key' => 'test-map-key']);
    $first = asUserWithInertia($this->user)->get(route('dashboard'))->assertOk();
    $catalogs = ['tenants', 'tags', 'eventTypes', 'institutionTypes', 'curatorDutyTypeIds', 'organization'];
    $first->assertJsonPath('props.map.cartoApiKey', 'test-map-key')
        ->assertJsonStructure(['props' => $catalogs]);
    expect(array_column($first->json('onceProps'), 'prop'))->toContain('map', ...$catalogs);

    $next = $this->withHeader('X-Inertia-Except-Once-Props', implode(',', array_keys($first->json('onceProps'))))
        ->get(route('dashboard'))->assertOk()
        ->assertJsonMissingPath('props.map')
        ->assertJsonStructure(['props' => ['typesenseConfig', 'ziggyContext', 'auth']]);

    foreach ($catalogs as $prop) {
        $next->assertJsonMissingPath('props.'.$prop);
    }
});

test('a changed catalog is resent on the next visit', function (): void {
    $first = asUserWithInertia($this->user)->get(route('dashboard'))->assertOk();
    $tag = Tag::factory()->create(['name' => ['lt' => 'Nauja žyma', 'en' => 'New tag']]);

    $next = $this->withHeader('X-Inertia-Except-Once-Props', implode(',', array_keys($first->json('onceProps'))))
        ->get(route('dashboard'))->assertOk()
        ->assertJsonMissingPath('props.tenants')
        ->assertJsonMissingPath('props.organization');

    expect(collect($next->json('props.tags'))->firstWhere('id', $tag->id)['name'])->toBe('Nauja žyma');
});

test('public catalogs remain available on first visits and use locale-specific once keys', function (): void {
    $first = asUserWithInertia($this->user)
        ->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))->assertOk()
        ->assertJsonStructure(['props' => ['tenants', 'organization', 'institutionTypes']]);
    $header = implode(',', array_keys($first->json('onceProps')));

    $this->withHeader('X-Inertia-Except-Once-Props', $header)
        ->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))->assertOk()
        ->assertJsonMissingPath('props.tenants')
        ->assertJsonMissingPath('props.organization');

    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'en']))->assertOk()
        ->assertJsonPath('props.app.locale', 'en')
        ->assertJsonStructure(['props' => ['tenants', 'organization', 'institutionTypes']]);
});

test('page-specific tenant lists override the once catalog without claiming its key', function (): void {
    $first = asUserWithInertia($this->user)
        ->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))->assertOk();

    $studySets = $this->withHeader('X-Inertia-Except-Once-Props', implode(',', array_keys($first->json('onceProps'))))
        ->get(route('studySets', ['lang' => 'lt']))->assertOk();

    expect(array_column($studySets->json('onceProps'), 'prop'))->not->toContain('tenants')
        ->and(array_column($studySets->json('props.tenants'), 'id'))->toBe(
            Tenant::query()->where('type', TenantType::Padalinys)->orderBy('shortname_vu')->pluck('id')->all()
        );

    $this->withHeader('X-Inertia-Except-Once-Props', implode(',', array_keys($studySets->json('onceProps'))))
        ->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))->assertOk()
        ->assertJsonCount(Tenant::query()->count(), 'props.tenants');
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
