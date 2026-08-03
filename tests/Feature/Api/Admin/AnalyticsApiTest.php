<?php

use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('services.umami.api_url', 'http://umami.test');
    config()->set('services.umami.website_id', 'test-website-id');
    config()->set('services.umami.username', 'api-user');
    config()->set('services.umami.password', 'secret');

    // The client caches both the token and the results; isolate every test.
    Cache::flush();
});

/**
 * @param  array<string, mixed>  $overrides
 */
function fakeUmami(array $overrides = []): void
{
    Http::fake(array_merge([
        'http://umami.test/api/auth/login' => Http::response(['token' => 'test-token']),
        'http://umami.test/api/websites/*/stats*' => Http::response([
            'pageviews' => 120, 'visitors' => 45, 'visits' => 60, 'bounces' => 12,
        ]),
        'http://umami.test/api/websites/*/pageviews*' => Http::response([
            'pageviews' => [['x' => '2026-07-25 00:00:00', 'y' => 70], ['x' => '2026-07-26 00:00:00', 'y' => 50]],
            'sessions' => [['x' => '2026-07-25 00:00:00', 'y' => 25], ['x' => '2026-07-26 00:00:00', 'y' => 20]],
        ]),
        'http://umami.test/api/websites/*/metrics*' => Http::response([
            ['x' => '/lt', 'y' => 80],
            ['x' => '/lt/naujienos', 'y' => 40],
        ]),
    ], $overrides));
}

test('requires authentication', function (): void {
    $tenant = Tenant::query()->first();

    $this->getJson(route('api.v1.admin.analytics.overview', ['tenant_id' => $tenant->id]))
        ->assertStatus(401);
});

test('returns tenant-scoped totals, series and top pages', function (): void {
    fakeUmami();

    $tenant = Tenant::query()->first();
    $user = makeTenantUser('Communication Coordinator', $tenant);

    $response = asUser($user)->getJson(
        route('api.v1.admin.analytics.overview', ['tenant_id' => $tenant->id])
    );

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.available', true)
        ->assertJsonPath('data.period', '30d')
        ->assertJsonPath('data.totals.pageviews', 120)
        ->assertJsonPath('data.totals.visitors', 45)
        ->assertJsonPath('data.series.0.date', '2026-07-25 00:00:00')
        ->assertJsonPath('data.series.0.pageviews', 70)
        ->assertJsonPath('data.series.0.visitors', 25)
        ->assertJsonPath('data.topPages.0.path', '/lt')
        ->assertJsonPath('data.topPages.0.views', 80);
});

test('scopes the upstream request to the tenant hostname', function (): void {
    fakeUmami();

    $tenant = Tenant::query()->first();
    $user = makeTenantUser('Communication Coordinator', $tenant);

    asUser($user)->getJson(route('api.v1.admin.analytics.overview', ['tenant_id' => $tenant->id]))
        ->assertSuccessful();

    // v3 spelling: `hostname=`. v2's `host=` is silently ignored and would return
    // unfiltered, cross-tenant totals.
    Http::assertSent(fn ($request) => str_contains($request->url(), '/stats')
        && $request['hostname'] === $tenant->publicHostname());
});

test('forbids reading a tenant the user does not manage', function (): void {
    fakeUmami();

    $ownTenant = Tenant::query()->first();
    $otherTenant = Tenant::query()->where('id', '!=', $ownTenant->id)->first();

    $user = makeTenantUser('Communication Coordinator', $ownTenant);

    asUser($user)->getJson(
        route('api.v1.admin.analytics.overview', ['tenant_id' => $otherTenant->id])
    )->assertStatus(403);
});

test('reports unavailable instead of failing when umami is down', function (): void {
    fakeUmami([
        'http://umami.test/api/websites/*/stats*' => Http::response(null, 500),
    ]);

    $tenant = Tenant::query()->first();
    $user = makeTenantUser('Communication Coordinator', $tenant);

    asUser($user)->getJson(route('api.v1.admin.analytics.overview', ['tenant_id' => $tenant->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.available', false)
        ->assertJsonPath('data.totals', null)
        ->assertJsonPath('data.series', []);
});

test('reports unavailable when the api is not configured', function (): void {
    config()->set('services.umami.api_url', null);

    $tenant = Tenant::query()->first();
    $user = makeTenantUser('Communication Coordinator', $tenant);

    asUser($user)->getJson(route('api.v1.admin.analytics.overview', ['tenant_id' => $tenant->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.available', false);

    Http::assertNothingSent();
});

test('re-authenticates once when the cached token has expired', function (): void {
    $statsCalls = 0;

    Http::fake([
        'http://umami.test/api/auth/login' => Http::response(['token' => 'test-token']),
        'http://umami.test/api/websites/*/stats*' => function () use (&$statsCalls) {
            $statsCalls++;

            // Expired token on the first attempt, success after re-auth.
            return $statsCalls === 1
                ? Http::response(null, 401)
                : Http::response(['pageviews' => 5, 'visitors' => 2, 'visits' => 3, 'bounces' => 1]);
        },
        'http://umami.test/api/websites/*/pageviews*' => Http::response(['pageviews' => [], 'sessions' => []]),
        'http://umami.test/api/websites/*/metrics*' => Http::response([]),
    ]);

    $tenant = Tenant::query()->first();
    $user = makeTenantUser('Communication Coordinator', $tenant);

    asUser($user)->getJson(route('api.v1.admin.analytics.overview', ['tenant_id' => $tenant->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.available', true)
        ->assertJsonPath('data.totals.pageviews', 5);

    expect($statsCalls)->toBe(2);
});

test('rejects an unknown period', function (): void {
    $tenant = Tenant::query()->first();
    $user = makeTenantUser('Communication Coordinator', $tenant);

    asUser($user)->getJson(
        route('api.v1.admin.analytics.overview', ['tenant_id' => $tenant->id, 'period' => 'forever'])
    )->assertStatus(422);
});

describe('content analytics', function (): void {
    test('returns lifetime views for a news article', function (): void {
        fakeUmami();

        $tenant = Tenant::query()->first();
        $news = News::factory()->create([
            'tenant_id' => $tenant->id,
            'lang' => 'lt',
            'permalink' => 'testine-naujiena',
        ]);

        asUser(makeAdminUser($tenant))->getJson(
            route('api.v1.admin.analytics.content', ['type' => 'news', 'id' => $news->id])
        )
            ->assertSuccessful()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.path', '/lt/naujiena/testine-naujiena')
            ->assertJsonPath('data.totals.pageviews', 120)
            ->assertJsonPath('data.dataSince', '2026-07-26');
    });

    test('uses the english url segment for english news', function (): void {
        fakeUmami();

        $tenant = Tenant::query()->first();
        $news = News::factory()->create([
            'tenant_id' => $tenant->id,
            'lang' => 'en',
            'permalink' => 'a-news-item',
        ]);

        asUser(makeAdminUser($tenant))->getJson(
            route('api.v1.admin.analytics.content', ['type' => 'news', 'id' => $news->id])
        )->assertJsonPath('data.path', '/en/news/a-news-item');
    });

    test('builds the page path without a news segment', function (): void {
        fakeUmami();

        $tenant = Tenant::query()->first();
        $page = Page::factory()->create([
            'tenant_id' => $tenant->id,
            'lang' => 'lt',
            'permalink' => 'apie-mus/kontaktai',
        ]);

        asUser(makeAdminUser($tenant))->getJson(
            route('api.v1.admin.analytics.content', ['type' => 'page', 'id' => $page->id])
        )->assertJsonPath('data.path', '/lt/apie-mus/kontaktai');
    });

    test('scopes the upstream query to that single path', function (): void {
        fakeUmami();

        $tenant = Tenant::query()->first();
        $news = News::factory()->create([
            'tenant_id' => $tenant->id,
            'lang' => 'lt',
            'permalink' => 'testine-naujiena',
        ]);

        asUser(makeAdminUser($tenant))->getJson(
            route('api.v1.admin.analytics.content', ['type' => 'news', 'id' => $news->id])
        )->assertSuccessful();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/stats')
            && $request['path'] === '/lt/naujiena/testine-naujiena'
            && $request['hostname'] === $tenant->publicHostname());
    });

    test('forbids reading content the user cannot edit', function (): void {
        fakeUmami();

        $tenant = Tenant::query()->first();
        $news = News::factory()->create(['tenant_id' => $tenant->id, 'lang' => 'lt', 'permalink' => 'x']);

        // A user with no roles at all has no update ability on this news item.
        asUser(makeTenantUser(null, $tenant))->getJson(
            route('api.v1.admin.analytics.content', ['type' => 'news', 'id' => $news->id])
        )->assertStatus(403);
    });

    test('rejects an arbitrary model type', function (): void {
        $tenant = Tenant::query()->first();

        asUser(makeAdminUser($tenant))->getJson(
            route('api.v1.admin.analytics.content', ['type' => 'user', 'id' => 1])
        )->assertStatus(422);
    });

    test('reports unavailable for content without a permalink', function (): void {
        fakeUmami();

        $tenant = Tenant::query()->first();

        // A null permalink is not indexable, so skip the search sync for this edge case.
        $news = News::withoutSyncingToSearch(
            fn () => News::factory()->create(['tenant_id' => $tenant->id, 'lang' => 'lt', 'permalink' => null])
        );

        asUser(makeAdminUser($tenant))->getJson(
            route('api.v1.admin.analytics.content', ['type' => 'news', 'id' => $news->id])
        )
            ->assertSuccessful()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.path', null);

        Http::assertNothingSent();
    });
});
