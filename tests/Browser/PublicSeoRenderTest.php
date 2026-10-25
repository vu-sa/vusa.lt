<?php

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Institution;
use App\Models\Page;
use App\Models\Tenant;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Uri;

pest()->use(RefreshDatabase::class);

it('keeps the server title after the public page mounts', function (Closure $arrange): void {
    $path = $arrange();
    $page = visitPublicSubdomain('www', $path);
    waitForInertiaRender($page, 'main h1');
    $page->assertNoJavaScriptErrors();

    $elements = $page->script('JSON.parse(document.querySelector("script[data-page]").textContent).props.head');
    $serverHead = HTMLDocument::createFromString('<!DOCTYPE html><html><head>'.implode('', $elements).'</head><body></body></html>', LIBXML_NOERROR)->head;

    expect($page->script('document.title'))->toBe($serverHead->querySelector('title')->textContent)
        ->and($page->script('document.querySelectorAll("head title").length'))->toBe(1)
        ->and($page->script('document.querySelector("meta[name=description]").content'))
        ->toBe($serverHead->querySelector('meta[name="description"]')->getAttribute('content'));

    if ($path === '/lt/naujienos') {
        $page->assertSee('Kas vyksta Studentų atstovybėje ir universitete');
    }

})->with([
    'home' => fn () => '/lt',
    'archive' => fn () => '/lt/naujienos',
    'documents' => fn () => '/lt/dokumentai',
    'content with a hidden title and a hero' => function (): string {
        $content = Content::factory()->create();
        ContentPart::factory()->create([
            'content_id' => $content->id,
            'type' => 'hero',
            'json_content' => ['title' => '<p>Student information</p>', 'description' => '', 'buttons' => []],
            'options' => ['variant' => 'centered'],
        ]);
        $page = Page::factory()->create([
            'tenant_id' => Tenant::main()->id,
            'title' => 'Student guide',
            'permalink' => 'seo-browser-page',
            'is_active' => true,
            'show_title' => false,
            'content_id' => $content->id,
        ]);

        return '/lt/'.$page->permalink;
    },
    'institution' => function (): string {
        $institution = Institution::factory()->create([
            'tenant_id' => Tenant::main()->id,
            'name' => ['lt' => 'Studijų kolegija', 'en' => 'Study committee'],
            'alias' => 'seo-browser-institution',
            'description' => ['lt' => '', 'en' => ''],
            'is_active' => true,
        ]);

        return '/'.ltrim(Uri::of($institution->publicUrl('lt'))->path(), '/');
    },
]);
