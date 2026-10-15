<?php

use App\Actions\PairTranslatedRecord;
use App\Models\ContentEditorDraft;
use App\Models\ContentPart;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\ContentEditorService;
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

it('edits ordinary text in the rich-content canvas and fits both themes at supported widths', function (): void {
    $this->page->content->parts()->delete();
    $this->page->content->parts()->create([
        'type' => 'tiptap', 'order' => 0,
        'json_content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Original text']]]]],
    ]);
    $page = loginAsAdmin($this->admin);
    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti turinį")');
    $page->click('button:has-text("Redaguoti turinį")');
    $page->page()->waitForSelector('[data-rc-fullscreen-scroll] .tiptap[contenteditable=true]');
    $page->page()->locator('[data-rc-fullscreen-scroll] .tiptap[contenteditable=true]')->fill('Updated text');
    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        foreach (['light', 'dark'] as $theme) {
            $page->script("document.documentElement.classList.toggle('dark', '$theme' === 'dark')");
            expect($page->script('document.querySelector("[data-rc-fullscreen-scroll]").scrollWidth <= document.querySelector("[data-rc-fullscreen-scroll]").clientWidth + 1'))->toBeTrue();
            $page->screenshot(fullPage: false, filename: "content-editor-$width-$theme");
        }
    }
    $page->assertNoJavaScriptErrors();
    $page->click('button[title="Uždaryti"]');
    $page->click('[data-testid=form-page-save]');
    waitForInertiaRender($page, '[role=status]:has-text("Visi pakeitimai išsaugoti")');
    expect($this->page->fresh()->content->parts->first()->json_content['content'][0]['content'][0]['text'])->toBe('Updated text');
});

it('edits a hero button through its hotspot popover and the change survives a save', function (): void {
    $page = loginAsAdmin($this->admin);

    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti turinį")');

    $page->click('button:has-text("Redaguoti turinį")');
    waitForInertiaRender($page, 'button:has-text("Registruotis")');

    // Click the button hotspot — opens a popover with the button's fields.
    $page->click('button:has-text("Registruotis")');
    $page->page()->waitForSelector('input[placeholder="Įvesk mygtuko tekstą..."]', ['timeout' => 10_000]);

    $textInput = $page->page()->locator('input[placeholder="Įvesk mygtuko tekstą..."]');
    $textInput->fill('Prisijungti dabar');

    // Close the full-screen editor — the same live array, so nothing is lost.
    $page->click('button[title="Uždaryti"]');
    $page->page()->waitForSelector('button:has-text("Redaguoti turinį")', ['timeout' => 10_000]);

    $page->click('[data-testid=form-page-save]');
    waitForInertiaRender($page, '[role=status]:has-text("Visi pakeitimai išsaugoti")');

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
    waitForInertiaRender($page, 'button:has-text("Redaguoti turinį")');
    $page->click('button:has-text("Redaguoti turinį")');
    waitForInertiaRender($page, 'button:has-text("Registruotis")');

    $page->click('button:has-text("Registruotis")');
    $page->page()->waitForSelector('input[placeholder="Įvesk mygtuko tekstą..."]', ['timeout' => 10_000]);

    $page->click('button:has-text("Sužinoti daugiau")');
    $page->page()->waitForSelector('input[placeholder="Įvesk mygtuko tekstą..."]', ['timeout' => 10_000]);

    $buttonTextInputs = 'document.querySelectorAll(\'input[placeholder="Įvesk mygtuko tekstą..."]\')';
    expect($page->script("{$buttonTextInputs}.length"))->toBe(1)
        ->and($page->script("{$buttonTextInputs}[0].value"))->toBe('Sužinoti daugiau');

    $page->assertNoJavaScriptErrors();
});

it('keeps a centered hero title uppercase and centered before, during, and after editing', function (): void {
    $this->page->content->parts()->first()->update([
        'options' => ['variant' => 'centered', 'textLeft' => true],
    ]);

    $page = loginAsAdmin($this->admin);
    expect($page->script('navigator.serviceWorker.getRegistrations().then((rs) => rs.length)'))->toBe(0);

    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti turinį")');
    $page->click('button:has-text("Redaguoti turinį")');
    waitForInertiaRender($page, '[role="heading"] button');

    $page->click('[role="heading"] button');
    $page->page()->waitForSelector('.rc-hero-title .ProseMirror', ['timeout' => 10_000]);
    $page->assertPresent('.rc-hero-title .ProseMirror');

    $page->click('button[title="Bloko nustatymai"]');
    $page->page()->waitForSelector('[role="heading"]', ['timeout' => 10_000]);
    $page->assertPresent('[role="heading"]');

    $page->assertNoJavaScriptErrors();
});

it('keeps the first split hero left-aligned and its settings trigger below the editor navbar', function (): void {
    $page = loginAsAdmin($this->admin);
    expect($page->script('navigator.serviceWorker.getRegistrations().then((rs) => rs.length)'))->toBe(0);

    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti turinį")');
    $page->click('button:has-text("Redaguoti turinį")');
    waitForInertiaRender($page, '[role="heading"] button');

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
    waitForInertiaRender($page, 'button:has-text("Redaguoti turinį")');
    $page->click('button:has-text("Redaguoti turinį")');
    waitForInertiaRender($page, '[data-testid="hero-image-spotlight-rail"]');

    $imageRight = $page->script('document.querySelector(".rc-fullscreen-block-display img").getBoundingClientRect().right');
    $spotlightRailLeft = $page->script('document.querySelector(\'[data-testid="hero-image-spotlight-rail"]\').getBoundingClientRect().left');

    expect($spotlightRailLeft)->toBeGreaterThanOrEqual($imageRight)
        ->and($page->script('document.querySelectorAll(\'[data-testid="hero-image-spotlight-rail"] [data-rc-interactive]\').length'))->toBe(3);

    $page->assertNoJavaScriptErrors();
});

it('shows the published hero without editing affordances in full-screen preview mode', function (): void {
    $page = loginAsAdmin($this->admin);
    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Redaguoti turinį")');
    $page->click('button:has-text("Redaguoti turinį")');
    waitForInertiaRender($page, '[data-rc-interactive]');

    $page->click('button[title="Peržiūrėti"][aria-pressed="false"]');
    $page->page()->unstrict(
        fn (Pest\Browser\Playwright\Page $playwrightPage) => $playwrightPage->waitForSelector(
            '[data-rc-interactive]',
            ['state' => 'detached', 'timeout' => 10_000],
        ),
    );

    expect($page->script('document.querySelectorAll("[data-rc-interactive]").length'))->toBe(0)
        ->and($page->script('document.querySelectorAll("button[title=\'Bloko nustatymai\']").length'))->toBe(0);

    $page->assertNoJavaScriptErrors();
});

it('restores a private server copy and saves it only after an explicit save', function (): void {
    $snapshot = app(ContentEditorService::class)->snapshot($this->page->fresh());
    $snapshot['title'] = 'Recovered title';
    ContentEditorDraft::create([
        'user_id' => $this->admin->id, 'kind' => 'pages', 'identity' => 'record-'.$this->page->id,
        'snapshot' => $snapshot, 'revision' => 1,
    ]);
    $page = loginAsAdmin($this->admin);
    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, '[data-testid=content-recovery] button:has-text("Atkurti kopiją")');
    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);
        foreach (['light', 'dark'] as $theme) {
            $page->script("document.documentElement.classList.toggle('dark', '$theme' === 'dark')");
            $page->screenshot(fullPage: false, filename: "content-recovery-$width-$theme");
        }
    }
    $page->click('[data-testid=content-recovery] button:has-text("Atkurti kopiją")');
    waitForInertiaRender($page, '[data-slot=content-recovery-status] [role=status]:has-text("Atkūrimo kopija išsaugota")');
    expect($this->page->fresh()->title)->toBe('Bandomasis puslapis');
    $page->click('[data-testid=form-page-save]');
    waitForInertiaRender($page, '[role=status]:has-text("Visi pakeitimai išsaugoti")');
    expect($this->page->fresh()->title)->toBe('Recovered title')
        ->and(ContentEditorDraft::count())->toBe(0);
    $page->assertNoJavaScriptErrors();
});

it('edits paired languages independently in the comparison workspace', function (): void {
    $this->page->content->parts()->delete();
    $this->page->content->parts()->create([
        'type' => 'tiptap', 'order' => 0,
        'json_content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'LT original']]]]],
    ]);
    $other = Page::factory()->for($this->page->tenant)->create(['lang' => 'en', 'is_active' => false]);
    $other->content->parts()->delete();
    $other->content->parts()->create([
        'type' => 'tiptap', 'order' => 0,
        'json_content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'EN original']]]]],
    ]);
    PairTranslatedRecord::execute($this->page, $other->id);
    $page = loginAsAdmin($this->admin);
    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Palyginti ir redaguoti")');
    $page->click('button:has-text("Palyginti ir redaguoti")');
    waitForInertiaRender($page, '[data-testid=translation-pane-en] .tiptap[contenteditable=true]');
    $page->page()->locator('[data-testid=translation-pane-lt] .tiptap[contenteditable=true]')->fill('LT updated');
    $page->page()->locator('[data-testid=translation-pane-en] .tiptap[contenteditable=true]')->fill('EN updated');
    $page->click('[data-testid=translation-pane-en] [data-testid=fullscreen-save]');
    waitForInertiaRender($page, '[data-testid=translation-pane-en][data-save-state=saved]');
    expect($other->fresh()->content->parts->first()->json_content['content'][0]['content'][0]['text'])->toBe('EN updated')
        ->and($this->page->fresh()->content->parts->first()->json_content['content'][0]['content'][0]['text'])->toBe('LT original');
    $page->click('[data-testid=translation-pane-lt] [data-testid=fullscreen-save]');
    waitForInertiaRender($page, '[data-testid=translation-pane-lt][data-save-state=saved]');
    expect($this->page->fresh()->content->parts->first()->json_content['content'][0]['content'][0]['text'])->toBe('LT updated')
        ->and($other->fresh()->is_active)->toBeFalse();
    $page->screenshot(fullPage: false, filename: 'content-translation-desktop');
    $page->resize(390, 900);
    $page->click('[data-testid=translation-locale-en]');
    expect($page->script('document.querySelector("[data-testid=translation-pane-en]").getBoundingClientRect().width <= 390'))->toBeTrue();
    $page->screenshot(fullPage: false, filename: 'content-translation-phone');
    $page->assertNoJavaScriptErrors();
});

it('creates an unpublished language copy from current edits without losing the source changes', function (): void {
    $page = loginAsAdmin($this->admin);
    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Sukurti versiją kita kalba")');
    $page->page()->locator('input#title')->fill('Unfinished source title');
    $page->click('button:has-text("Sukurti versiją kita kalba")');
    waitForInertiaRender($page, '[data-testid=translation-pane-en] [data-testid=translation-copy]');
    $input = $page->page()->locator('[data-testid=translation-pane-en] input[data-slot=input]');
    $page->click('[data-testid=translation-copy] button');
    expect($input->inputValue())->toBe('Unfinished source title');
    $input->fill('English title');
    $page->click('[data-testid=translation-pane-en] [data-testid=fullscreen-save]');
    waitForInertiaRender($page, '[data-testid=translation-pane-en][data-save-state=saved]');
    $other = Page::findOrFail($this->page->fresh()->other_lang_id);
    expect($other->title)->toBe('English title')
        ->and($other->is_active)->toBeFalse()
        ->and($this->page->fresh()->title)->toBe('Bandomasis puslapis');
    $page->click('[data-testid=translation-pane-lt] [data-testid=fullscreen-save]');
    waitForInertiaRender($page, '[data-testid=translation-pane-lt][data-save-state=saved]');
    expect($this->page->fresh()->title)->toBe('Unfinished source title')
        ->and($this->page->fresh()->other_lang_id)->toBe($other->id);
    $page->assertNoJavaScriptErrors();
});

it('starts a new language version blank and copies the source only on request', function (): void {
    $page = loginAsAdmin($this->admin);
    $page->navigate("/mano/pages/{$this->page->id}/edit");
    waitForInertiaRender($page, 'button:has-text("Sukurti versiją kita kalba")');
    $page->click('button:has-text("Sukurti versiją kita kalba")');
    waitForInertiaRender($page, '[data-testid=translation-pane-en] .tiptap[contenteditable=true]');
    $input = $page->page()->locator('[data-testid=translation-pane-en] input[data-slot=input]');
    expect($input->inputValue())->toBe('')
        ->and($page->script('document.querySelector("[data-testid=translation-pane-en] [role=alert]") === null'))->toBeTrue()
        ->and($page->script('document.querySelectorAll("[data-testid=translation-pane-en] [data-rc-block-key]").length'))->toBe(1);
    $page->click('[data-testid=translation-copy] button');
    expect($input->inputValue())->toBe('Bandomasis puslapis')
        ->and($page->script('document.querySelector("[data-testid=translation-copy]") === null'))->toBeTrue();
    $page->assertNoJavaScriptErrors();
});
