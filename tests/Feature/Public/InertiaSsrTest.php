<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Vite::useHotFile(storage_path('framework/ssr-test-no-hot'));
    config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false]);
    $this->tenant = Tenant::where('alias', 'vusa')->firstOrFail();
    Page::factory()->create([
        'tenant_id' => $this->tenant->id,
        'title' => 'SSR puslapis',
        'lang' => 'lt',
        'permalink' => 'ssr-page',
        'is_active' => true,
    ]);
    $this->url = route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'ssr-page']);
    $this->ssrStatus = 200;
    $this->ssrResponse = ['head' => [], 'body' => '<div id="app" data-server-rendered="true">SSR page rendered</div>'];
    Http::fake(['127.0.0.1:13714/*' => fn () => Http::response($this->ssrResponse, $this->ssrStatus)]);
});

it('renders anonymous pilot pages through Inertia SSR with public routes', function (): void {
    $this->get($this->url)->assertOk()->assertSee('SSR page rendered');
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/render')
        && $request['component'] === 'Public/ContentPage'
        && $request['props']['auth'] === null
        && isset($request['props']['ziggy']['routes']['login'])
        && ! isset($request['props']['ziggy']['routes']['pages.index']));
});

it('leaves the SSR route list out of client-side Inertia visits', function (): void {
    $this->get($this->url, [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
    ])->assertOk()->assertJsonMissingPath('props.ziggy');
    Http::assertNothingSent();
});

it('keeps signed-in visits on client rendering', function (): void {
    $this->actingAs(makeUser($this->tenant))->get($this->url)->assertOk()
        ->assertDontSee('SSR page rendered');
    Http::assertNothingSent();
});

it('keeps other public routes outside the pilot', function (): void {
    $this->get(route('newsArchive', ['subdomain' => 'www', 'lang' => 'lt']))->assertOk()
        ->assertDontSee('SSR page rendered');
    Http::assertNothingSent();
});

it('falls back to client rendering when the renderer fails', function (): void {
    $this->ssrStatus = 500;
    $this->ssrResponse = ['error' => 'Render failed'];
    $this->get($this->url)->assertOk()->assertSee('data-page="app"', false)
        ->assertDontSee('data-server-rendered', false);
});

it('does not contact a renderer when SSR is disabled', function (): void {
    config(['inertia.ssr.enabled' => false]);
    $this->get($this->url)->assertOk()->assertDontSee('SSR page rendered');
    Http::assertNothingSent();
});
