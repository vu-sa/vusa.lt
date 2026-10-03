<?php

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Inertia\Ssr\SsrState;

pest()->use(RefreshDatabase::class);

it('hydrates an anonymous public page without losing its published content', function (): void {
    config(['inertia.ssr.enabled' => false, 'inertia.devtools.enabled' => false]);
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => ['type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Published SSR content']]],
        ]],
    ]);
    Page::factory()->create([
        'tenant_id' => Tenant::where('alias', 'vusa')->firstOrFail()->id,
        'title' => 'SSR hydration page',
        'permalink' => 'ssr-hydration-page',
        'lang' => 'lt',
        'content_id' => $content->id,
        'is_active' => true,
    ]);

    // Pest keeps the app alive across its warm-up and target requests.
    Event::listen(RouteMatched::class, function (RouteMatched $event): void {
        app()->forgetInstance(SsrState::class);
        config(['inertia.ssr.enabled' => $event->route->getName() === 'page']);
    });
    config(['inertia.ssr.throw_on_error' => true]);
    $page = visitPublicSubdomain('www', '/lt/ssr-hydration-page');
    waitForInertiaRender($page);
    $page->assertSee('Published SSR content')
        ->assertSee('Puslapio informacija paskutinį kartą atnaujinta')
        ->assertNoJavaScriptErrors()->assertNoConsoleLogs();
    expect($page->script('document.querySelector("#app").hasAttribute("data-server-rendered")'))->toBeTrue();
    $page->resize(390, 844)->assertSee('Published SSR content');
});
