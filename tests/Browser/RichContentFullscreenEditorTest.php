<?php

use App\Models\ContentPart;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The full-screen editor's contextual popovers depend on real Floating UI positioning
 * and real click/focus semantics through a portal — none of which jsdom can compute (see
 * RCFullscreenEditor.vue's plan). This is the real-browser gate for the Hero migration:
 * open the editor, edit a button through its hotspot popover, and confirm the change
 * actually persists through a save.
 */
beforeEach(function (): void {
    $tenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    $this->admin = makeAdminUser($tenant);

    $this->page = Page::factory()->for($tenant)->create(['title' => 'Bandomasis puslapis']);
    // Drop the factory's default tiptap part so the hero block is the only one — keeps
    // hotspot selectors unambiguous.
    $this->page->content->parts()->delete();

    $this->page->content->parts()->create([
        'type' => 'hero',
        'order' => 0,
        'json_content' => [
            'title' => 'Prisijunk prie mūsų',
            'description' => 'Aprašymas',
            'eyebrow' => '',
            'imageSrc' => '',
            'imageAlt' => '',
            'buttons' => [
                ['text' => 'Registruotis', 'link' => '#registruotis', 'variant' => 'default'],
            ],
        ],
        'options' => ['variant' => 'split', 'textLeft' => true],
    ]);
});

it('edits a hero button through its hotspot popover and the change survives a save', function (): void {
    $page = loginAsAdmin($this->admin);

    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti per visą ekraną")');

    $page->click('button:has-text("Redaguoti per visą ekraną")');
    waitForInertiaRender($page, 'button:has-text("Registruotis")');

    // Click the button hotspot — opens a popover with the button's fields.
    $page->click('button:has-text("Registruotis")');
    $page->page()->waitForSelector('input[placeholder="Įveskite mygtuko tekstą..."]', ['timeout' => 10_000]);

    $textInput = $page->page()->locator('input[placeholder="Įveskite mygtuko tekstą..."]');
    $textInput->fill('Prisijungti dabar');

    // Close the full-screen editor — the same live array, so nothing is lost.
    $page->click('button[title="Uždaryti"]');
    $page->page()->waitForSelector('button:has-text("Redaguoti per visą ekraną")', ['timeout' => 10_000]);

    $page->click('button:has-text("Išsaugoti")');
    waitForInertiaRender($page, '.text-green-600:has-text("Išsaugota")');

    expect(
        ContentPart::query()->where('content_id', $this->page->content_id)->first()->json_content['buttons'][0]['text']
    )->toBe('Prisijungti dabar');

    $page->assertNoJavaScriptErrors();
});

it('opening a second hotspot visually closes the first', function (): void {
    $this->page->content->parts()->first()->update([
        'json_content' => [
            'title' => 'Prisijunk prie mūsų',
            'description' => 'Aprašymas',
            'eyebrow' => '',
            'imageSrc' => '',
            'imageAlt' => '',
            'buttons' => [
                ['text' => 'Registruotis', 'link' => '#a', 'variant' => 'default'],
                ['text' => 'Sužinoti daugiau', 'link' => '#b', 'variant' => 'outline'],
            ],
        ],
    ]);

    $page = loginAsAdmin($this->admin);
    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti per visą ekraną")');
    $page->click('button:has-text("Redaguoti per visą ekraną")');
    waitForInertiaRender($page, 'button:has-text("Registruotis")');

    $page->click('button:has-text("Registruotis")');
    $page->page()->waitForSelector('input[placeholder="Įveskite mygtuko tekstą..."]', ['timeout' => 10_000]);

    $page->click('button:has-text("Sužinoti daugiau")');
    $page->page()->waitForSelector('input[placeholder="Įveskite mygtuko tekstą..."]', ['timeout' => 10_000]);

    $buttonTextInputs = 'document.querySelectorAll(\'input[placeholder="Įveskite mygtuko tekstą..."]\')';
    expect($page->script("{$buttonTextInputs}.length"))->toBe(1);
    expect($page->script("{$buttonTextInputs}[0].value"))->toBe('Sužinoti daugiau');

    $page->assertNoJavaScriptErrors();
});

it('keeps a centered hero title uppercase and centered before, during, and after editing', function (): void {
    $this->page->content->parts()->first()->update([
        'options' => ['variant' => 'centered', 'textLeft' => true],
    ]);

    $page = loginAsAdmin($this->admin);
    expect($page->script('navigator.serviceWorker.getRegistrations().then((rs) => rs.length)'))->toBe(0);

    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti per visą ekraną")');
    $page->click('button:has-text("Redaguoti per visą ekraną")');
    waitForInertiaRender($page, '[role="heading"] button');

    expect($page->script('getComputedStyle(document.querySelector(".rc-hero-title button")).textTransform'))->toBe('uppercase');
    expect($page->script('getComputedStyle(document.querySelector(".rc-hero-title button")).textAlign'))->toBe('center');

    $page->click('[role="heading"] button');
    $page->page()->waitForSelector('.rc-hero-title .ProseMirror', ['timeout' => 10_000]);

    expect($page->script('getComputedStyle(document.querySelector(".rc-hero-title .ProseMirror p")).textTransform'))->toBe('uppercase');
    expect($page->script('getComputedStyle(document.querySelector(".rc-hero-title .ProseMirror p")).textAlign'))->toBe('center');

    $page->click('button[title="Bloko nustatymai"]');
    $page->page()->waitForSelector('[role="heading"]', ['timeout' => 10_000]);

    expect($page->script('getComputedStyle(document.querySelector(".rc-hero-title button")).textTransform'))->toBe('uppercase');
    expect($page->script('getComputedStyle(document.querySelector(".rc-hero-title button")).textAlign'))->toBe('center');

    $page->assertNoJavaScriptErrors();
});

it('keeps the first split hero left-aligned and its settings trigger below the editor navbar', function (): void {
    $page = loginAsAdmin($this->admin);
    expect($page->script('navigator.serviceWorker.getRegistrations().then((rs) => rs.length)'))->toBe(0);

    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti per visą ekraną")');
    $page->click('button:has-text("Redaguoti per visą ekraną")');
    waitForInertiaRender($page, '[role="heading"] button');

    expect($page->script('getComputedStyle(document.querySelector(".rc-hero-title")).textAlign'))->toBe('left');
    expect($page->script('getComputedStyle(document.querySelector(".rc-hero-title button")).textTransform'))->toBe('uppercase');
    $toolbarTop = $page->script('document.querySelector(\'button[title="Bloko nustatymai"]\').getBoundingClientRect().top');
    $navbarBottom = $page->script('document.querySelector(".sticky.top-0").getBoundingClientRect().bottom');

    expect($toolbarTop)->toBeGreaterThanOrEqual($navbarBottom);

    $page->assertNoJavaScriptErrors();
});

it('keeps split hero image spotlights beside the image surface', function (): void {
    $part = $this->page->content->parts()->first();
    $part->update([
        'json_content' => [
            ...$part->json_content,
            'imageSrc' => '/images/photos/vusa.jpg',
        ],
    ]);

    $page = loginAsAdmin($this->admin);
    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti per visą ekraną")');
    $page->click('button:has-text("Redaguoti per visą ekraną")');
    waitForInertiaRender($page, '[data-testid="hero-image-spotlight-rail"]');

    $imageRight = $page->script('document.querySelector(".rc-fullscreen-block-display img").getBoundingClientRect().right');
    $spotlightRailLeft = $page->script('document.querySelector(\'[data-testid="hero-image-spotlight-rail"]\').getBoundingClientRect().left');

    expect($spotlightRailLeft)->toBeGreaterThanOrEqual($imageRight);
    expect($page->script('document.querySelectorAll(\'[data-testid="hero-image-spotlight-rail"] [data-rc-interactive]\').length'))->toBe(3);
    $imageRatio = $page->script('(() => { const image = document.querySelector(".rc-fullscreen-block-display img").getBoundingClientRect(); return image.width / image.height; })()');
    expect($imageRatio)->toBeGreaterThan(1.55)->toBeLessThan(1.65);

    $page->assertNoJavaScriptErrors();
});

it('shows the published hero without editing affordances in full-screen preview mode', function (): void {
    $page = loginAsAdmin($this->admin);
    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti per visą ekraną")');
    $page->click('button:has-text("Redaguoti per visą ekraną")');
    waitForInertiaRender($page, '[data-rc-interactive]');

    $page->click('button[aria-pressed="false"]');
    $page->page()->waitForSelector('[data-rc-interactive]', ['state' => 'detached', 'timeout' => 10_000]);

    expect($page->script('document.querySelectorAll("[data-rc-interactive]").length'))->toBe(0);
    expect($page->script('document.querySelectorAll("button[title=\'Bloko nustatymai\']").length'))->toBe(0);

    $page->assertNoJavaScriptErrors();
});
