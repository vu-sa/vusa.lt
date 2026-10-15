<?php

use App\Models\ContentEditorDraft;
use App\Models\News;
use App\Models\Page;
use App\Models\User;
use App\Services\ContentEditorService;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Written Lithuanian content and a paired English page, so the editors show what authors see.
    $this->seed(DocsSeeder::class);

    $this->coordinator = User::query()->firstWhere('email', DocsSeeder::COMMUNICATION_COORDINATOR_EMAIL);
});

it('opens a news item in its editor within the viewport', function (): void {
    $news = News::query()->where('title', DocsSeeder::NEWS_TITLE)->firstOrFail();

    $page = loginAsAdmin($this->coordinator);
    $page->navigate("/mano/news/{$news->id}/edit");
    waitForInertiaRender($page, '[data-slot=form-page] .tiptap');

    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);

        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
    }

    $page->resize(1440, 1000);
    docsScreenshot($page, 'news-form');
    docsScreenshot($page, 'v3-forms', highlights: [
        '[data-testid=form-page-bar]',
        '[data-testid=form-page-aside] > :first-child',
    ]);

    $page->assertNoJavaScriptErrors();
});

it('compares both language versions of a page side by side on a desktop', function (): void {
    $lithuanian = Page::query()->where('title', DocsSeeder::PAGE_TITLE)->firstOrFail();

    $page = loginAsAdmin($this->coordinator);
    $page->resize(1440, 1000);
    $page->navigate("/mano/pages/{$lithuanian->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Palyginti ir redaguoti")');
    $page->click('button:has-text("Palyginti ir redaguoti")');
    waitForInertiaRender($page, '[data-testid=translation-pane-en] .tiptap[contenteditable=true]');

    $panes = $page->script(<<<'JS'
    ['lt', 'en'].map(locale => {
      const box = document.querySelector(`[data-testid=translation-pane-${locale}]`).getBoundingClientRect();
      return { left: box.left, right: box.right };
    })
    JS);

    // Side by side: the English pane starts where the Lithuanian one ends, both inside the window.
    expect($panes[0]['right'])->toBeLessThanOrEqual($panes[1]['left'] + 1)
        ->and($panes[1]['right'])->toBeLessThanOrEqual(1441);

    $page->script('document.activeElement?.blur()');
    // The workspace fills the window; a shorter one keeps the frame from being mostly empty canvas.
    $page->resize(1440, 620);
    docsScreenshot($page, 'page-form');
    docsScreenshot($page, 'v3-content-languages', highlights: [
        '[data-testid=translation-pane-lt] > :first-child',
        '[data-testid=translation-pane-en] > :first-child',
        '[data-testid=translation-pane-lt] [data-testid=fullscreen-save]',
        '[data-testid=translation-pane-en] [data-testid=fullscreen-save]',
    ]);

    $page->assertNoJavaScriptErrors();
});

it('offers to restore unsaved page changes kept on the server', function (): void {
    $lithuanian = Page::query()->where('title', DocsSeeder::PAGE_TITLE)->firstOrFail();
    $snapshot = app(ContentEditorService::class)->snapshot($lithuanian);
    ContentEditorDraft::query()->create([
        'user_id' => $this->coordinator->id,
        'kind' => 'pages',
        'identity' => "record-{$lithuanian->id}",
        'snapshot' => [...$snapshot, 'title' => 'Kaip tapti studentų atstovu: rinkimai 2026'],
        'revision' => 1,
    ]);

    $page = loginAsAdmin($this->coordinator);
    $page->resize(1440, 900);
    $page->navigate("/mano/pages/{$lithuanian->id}/edit");
    waitForInertiaRender($page, '[data-testid=content-recovery] button:has-text("Atkurti kopiją")');

    $page->assertSee('Kaip tapti studentų atstovu: rinkimai 2026');
    $page->page()->locator('[data-testid=content-recovery]')->scrollIntoViewIfNeeded();
    docsScreenshot($page, 'page-recovery');

    $page->assertNoJavaScriptErrors();
});
