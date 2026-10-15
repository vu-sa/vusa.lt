<?php

use App\Models\QuickLink;
use App\Models\Tenant;
use App\Services\PublicAssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->cataloguePath = tempnam(sys_get_temp_dir(), 'public-icons-');
    $this->calendarIcon = ['body' => '<path d="M1 2h3v4H1z"/>', 'width' => 24, 'height' => 24];
    File::put($this->cataloguePath, json_encode([
        'calendar-24-regular' => $this->calendarIcon,
        'people-24-regular' => ['body' => '<circle cx="12" cy="12" r="3"/>', 'width' => 24, 'height' => 24],
    ]));
    $this->app->instance(PublicAssetService::class, new PublicAssetService($this->cataloguePath));
    $this->tenant = Tenant::where('alias', 'vusa')->firstOrFail();
    config(['inertia.ssr.enabled' => false]);
});

afterEach(function (): void {
    File::delete($this->cataloguePath, $this->cataloguePath.'.version');
});

it('shares only the selected tenant and language icons for anonymous and signed-in visits', function (bool $signedIn): void {
    QuickLink::factory()->create([
        'tenant_id' => $this->tenant->id, 'lang' => 'en', 'icon' => 'calendar-24-regular',
    ]);
    QuickLink::factory()->create([
        'tenant_id' => $this->tenant->id, 'lang' => 'lt', 'icon' => 'people-24-regular',
    ]);
    QuickLink::factory()->create(['lang' => 'en', 'icon' => 'people-24-regular']);

    if ($signedIn) {
        $this->actingAs(makeUser($this->tenant));
    }

    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'en']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('app.locale', 'en')
            ->where('publicAssets.logoSrc', '/logos/hor/en/vusa.lin.hor.tams.svg')
            ->where('publicAssets.icons', ['calendar-24-regular' => $this->calendarIcon])
            ->has('tenant.links', 1));
})->with([false, true]);

it('preloads the same tenant and language logo supplied to the header', function (string $alias, string $locale, string $logo): void {
    $subdomain = $alias === 'vusa' ? 'www' : $alias;
    $this->get(route('home', ['subdomain' => $subdomain, 'lang' => $locale]))
        ->assertOk()
        ->assertSee('<link rel="preload" as="image" href="'.$logo.'" fetchpriority="high">', false)
        ->assertInertia(fn (Assert $page) => $page->where('publicAssets.logoSrc', $logo));
})->with([
    ['vusa', 'en', '/logos/hor/en/vusa.lin.hor.tams.svg'],
    ['mif', 'lt', '/logos/hor/lt/vusamif.lin.hor.tams.svg'],
    ['chgf', 'lt', '/logos/hor/lt/vusachgf.lin.hor.balt.svg'],
    ['sa', 'en', '/logos/hor/en/vusasa.lin.hor.tams.en.svg'],
]);

it('omits unavailable icons without losing their links', function (): void {
    QuickLink::factory()->create([
        'tenant_id' => $this->tenant->id, 'lang' => 'en', 'icon' => 'not-an-icon',
    ]);
    File::delete($this->cataloguePath);

    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'en']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('publicAssets.icons', 0)
            ->where('tenant.links.0.icon', 'not-an-icon'));
});

it('loads new icon data when the catalogue version changes', function (): void {
    $service = app(PublicAssetService::class);
    File::put($this->cataloguePath.'.version', 'first');
    expect($service->icons(['calendar-24-regular']))->toBe(['calendar-24-regular' => $this->calendarIcon]);

    $updated = ['body' => '<path d="M0 0h24v24H0z"/>', 'width' => 24, 'height' => 24];
    File::put($this->cataloguePath, json_encode(['calendar-24-regular' => $updated]));
    File::put($this->cataloguePath.'.version', 'second');

    expect($service->icons(['calendar-24-regular']))->toBe(['calendar-24-regular' => $updated]);
});

it('ignores unknown names and malformed catalogue entries', function (): void {
    File::put($this->cataloguePath, json_encode([
        'calendar-24-regular' => $this->calendarIcon,
        'malformed' => ['body' => null],
    ]));

    expect(app(PublicAssetService::class)->icons(['calendar-24-regular', 'missing', 'malformed']))
        ->toBe(['calendar-24-regular' => $this->calendarIcon]);
});

it('falls back when the catalogue is unreadable JSON', function (): void {
    File::put($this->cataloguePath, 'incomplete catalogue');

    expect(app(PublicAssetService::class)->icons(['calendar-24-regular']))->toBeEmpty();
});
