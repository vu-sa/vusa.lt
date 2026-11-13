<?php

use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

it('keeps an archive document when the editor switches source modes and explains the audience', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    foreach (['knowledgeItems.update.*', 'learningSteps.update.*'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $document = Document::factory()->create();
    $item = KnowledgeItem::factory()->withoutAudiences()->create();
    $item->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    $page = loginAsAdmin($user, route('knowledgeItems.edit', $item, absolute: false), 1440, 900);
    $page->assertVisible('[data-testid=selected-document-lt]')
        ->click('[data-testid=source-mode-lt-url]')->type('[data-testid=source-url-lt]', 'https://example.test/reference')
        ->click('[data-testid=source-mode-lt-document]')->assertVisible('[data-testid=selected-document-lt]')
        ->assertSee('Tau skirti įrašai')->screenshotElement('[data-testid=source-block-lt]', filename: 'wiki-source-switch-detail');
    $page->resize(390, 844)->assertVisible('[data-slot=knowledge-audience-hint]')
        ->assertNoJavaScriptErrors()->screenshotElement('[data-testid=step-audiences]', filename: 'wiki-audience-hint-phone');
});

it('keeps the library open action and edit menu inside the table action column', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('knowledgeItems.update.*', 'web'));
    KnowledgeItem::factory()->withoutAudiences()->create();
    $page = loginAsAdmin($user, route('knowledgeItems.index', ['view' => 'table'], absolute: false), 1440, 900);
    foreach ([1440, 820] as $width) {
        $page->resize($width, 844)->assertAttribute('[data-collection-row-action=primary]', 'data-action', 'open');
        expect($page->script('(() => { const actions = document.querySelector("[data-slot=collection-row-actions]"); const cell = actions.closest("td"); return actions.scrollWidth <= cell.clientWidth; })()'))->toBeTrue();
        $page->assertNoJavaScriptErrors()->screenshot(filename: 'wiki-library-actions-'.$width);
    }
    $page->resize(390, 844)->click('[data-slot=record-action-menu] button:visible')
        ->assertVisible('[data-action=edit]')
        ->assertNoJavaScriptErrors()->screenshot(filename: 'wiki-library-actions-phone');
});

it('keeps alphabet headings below both sticky navigation bars after a letter jump', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    foreach (['A', 'B', 'C', 'D', 'E', 'Z'] as $letter) {
        KnowledgeItem::factory()->withoutAudiences()->count(4)->create([
            'title' => ['lt' => $letter.' straipsnis', 'en' => $letter.' article'],
        ]);
    }
    $page = loginAsAdmin($user, route('knowledgeItems.alphabet', absolute: false), 1440, 900);
    foreach ([1440, 390] as $width) {
        $page->resize($width, 844)->click('a[href="#letter-c"]')->assertVisible('#letter-c-title');
        expect($page->script('document.querySelector("#letter-c-title").getBoundingClientRect().top >= document.querySelector("[data-slot=alphabet-bar]").getBoundingClientRect().bottom'))->toBeTrue();
        $page->assertNoJavaScriptErrors()->screenshot(filename: 'wiki-alphabet-jump-'.$width);
    }
});

it('opens articles for you from the Wiki menu and switches grouping on its own page', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = makeUser($tenant);
    grantPurpose($user->duties()->firstOrFail(), ResponsibilityPurpose::StudentRepresentation);
    $coordinator = makeUser($tenant);
    $coordinator->update(['name' => 'Ona Koordinatorė']);
    $coordinatorDuty = $coordinator->duties()->firstOrFail();
    $coordinatorDuty->update(['name' => ['lt' => 'Koordinatorius', 'en' => 'Coordinator'], 'email' => 'koordinatorius@vusa.lt']);
    grantPurpose($coordinatorDuty, ResponsibilityPurpose::StudentRepCoordination, $tenant);
    $section = KnowledgeSection::factory()->create(['title' => ['lt' => 'Studijos', 'en' => 'Studies']]);
    foreach (['essential', 'recommended', 'optional'] as $importance) {
        $items = KnowledgeItem::factory()->audience(['importance' => $importance])->count(2)->create(['knowledge_section_id' => $section->id]);
        $items->first()->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()]);
    }
    KnowledgeItem::factory()->withoutAudiences()->create();
    $page = loginAsAdmin($user, route('knowledgeBase.show', absolute: false), 1440, 900);
    $page->assertVisible('[data-slot=wiki-search] input')->assertVisible('[data-slot=wiki-progress-all]')
        ->assertNotPresent('[data-slot=wiki-progress-assigned]')->screenshot(filename: 'wiki-home-search');
    $page->click('[data-slot=section-tabs] a[href$="/knowledge/for-you"]')
        ->assertPathIs('/mano/knowledge/for-you')
        ->assertVisible('[data-slot=wiki-progress-assigned]')->assertNotPresent('[data-slot=wiki-progress-all]')
        ->assertVisible('[data-testid=documents-view-importance]');
    $page->click('[data-testid=documents-view-sections]')->assertSee('Studijos')
        ->assertAttribute('[data-testid=documents-view-sections]', 'aria-pressed', 'true')
        ->screenshot(filename: 'wiki-for-you-desktop');
    $page->resize(390, 844)->assertNoJavaScriptErrors()->screenshot(filename: 'wiki-for-you-phone');
    $page->script('document.documentElement.classList.add("dark")');
    $page->screenshot(filename: 'wiki-progress-phone-dark');
    $page->resize(1440, 900)->screenshot(filename: 'wiki-progress-desktop-dark');
    navigateTo($page, route('knowledgeBase.mine', ['view' => 'sections'], absolute: false));
    $page->assertAttribute('[data-testid=documents-view-sections]', 'aria-pressed', 'true');
    navigateTo($page, route('knowledgeBase.show', absolute: false));
    $page->assertVisible('[data-slot=wiki-search] input')->screenshot(filename: 'wiki-home-search-phone');
    navigateTo($page, route('learningPath.show', absolute: false));
    $page->assertVisible('[data-slot=documents-entry] [data-slot=wiki-progress-assigned]')
        ->assertNotPresent('[data-slot=learning-summary]')->assertNotPresent('[data-slot=wiki-progress-legend]')
        ->assertNoJavaScriptErrors()->screenshot(filename: 'learning-wiki-progress-desktop');
    expect($page->script('(() => { const block = document.querySelector("[data-slot=documents-entry]"); const read = block.querySelector("[data-slot=learning-step-action]").getBoundingClientRect(); const wiki = block.querySelector("[data-slot=all-documents]").getBoundingClientRect(); return read.width === wiki.width && read.height === wiki.height; })()'))->toBeTrue();
    $page->assertVisible('[data-slot=learning-coordinators]')->screenshotElement('[data-step]:has([data-slot=learning-coordinators])', filename: 'learning-coordinator-desktop');
    $page->resize(390, 844)->screenshot(filename: 'learning-wiki-progress-phone');
    $page->screenshotElement('[data-step]:has([data-slot=learning-coordinators])', filename: 'learning-coordinator-phone');
});

it('lets a reader complete an off-path article in the built app', function (): void {
    Storage::fake();
    Storage::put('document-thumbnails/wiki-preview.png', UploadedFile::fake()->image('preview.png', 300, 400)->getContent());
    $user = makeUser(Tenant::query()->firstOrFail());
    $related = KnowledgeItem::factory()->withoutAudiences()->create([
        'title' => ['lt' => 'Studijų programos komitetas', 'en' => 'Study programme committee'],
        'abbreviation' => ['lt' => 'SPK', 'en' => 'SPC'],
        'summary' => ['lt' => 'Komitetas rūpinasi studijų programa.', 'en' => 'The committee oversees the study programme.'],
    ]);
    $related->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()]);
    $item = KnowledgeItem::factory()->withoutAudiences()->create([
        'description' => ['lt' => '<p><a data-knowledge-id="'.$related->id.'" href="/x">SPK</a></p>', 'en' => '<p>Article text.</p>'],
    ]);
    $document = Document::factory()->create(['title' => 'Studijų programos komiteto nuostatai', 'thumbnail_path' => 'document-thumbnails/wiki-preview.png', 'thumbnail_etag' => 'preview']);
    $item->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    $page = loginAsAdmin($user, route('knowledgeItems.show', $item, absolute: false), 390, 844);
    $page->assertPresent('[data-testid=step-checkbox]')
        ->assertPresent('[data-slot=step-about] [data-slot=article-related] [data-slot=wiki-page-learned]')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'wiki-article-phone');
    $page->screenshotElement('[data-slot=source-preview]', filename: 'wiki-source-preview-phone');
    expect($page->script('(() => { const preview = document.querySelector("[data-slot=source-preview]"); return preview.querySelector("a > span:last-child").getBoundingClientRect().top >= preview.querySelector("img").getBoundingClientRect().bottom; })()'))->toBeTrue();
    $page->click('[data-testid=step-checkbox]')->assertAttribute('[data-testid=step-checkbox]', 'aria-checked', 'true');
    foreach ([820, 1180, 1440] as $width) {
        $page->resize($width, 900)->screenshot(filename: 'wiki-article-'.$width);
    }
    $page->hover('[data-slot=article-related] a')->assertVisible('[data-slot=wiki-link-card]')
        ->assertSee('Komitetas rūpinasi studijų programa.')
        ->screenshot(filename: 'wiki-related-preview');
    $page->script('document.documentElement.classList.add("dark")');
    $page->screenshot(filename: 'wiki-related-preview-dark');
    navigateTo($page, route('knowledgeItems.show', $item, absolute: false));
    $page->assertAttribute('[data-testid=step-checkbox]', 'aria-checked', 'true')->assertNoJavaScriptErrors();
});

it('opens the section manager and bilingual editor in the built app', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    foreach (['knowledgeItems.create.*', 'knowledgeItems.update.*', 'learningSteps.create.*', 'learningSteps.update.*'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    KnowledgeSection::factory()->count(2)->create();
    $page = loginAsAdmin($user, route('knowledgeItems.index', absolute: false), 390, 844);
    $page->click('[data-testid=manage-sections]')->assertPresent('[data-slot=managed-section]')
        ->screenshot(filename: 'wiki-sections-phone');
    $page->click('[data-testid=create-section]')->click('[data-testid=section-locale-en]')
        ->assertPresent('[data-testid=section-title]')->screenshot(filename: 'wiki-section-editor-phone');
    $page->resize(1440, 900)->screenshot(filename: 'wiki-section-editor-desktop');
    $page->script('document.documentElement.classList.add("dark")');
    $page->screenshot(filename: 'wiki-section-editor-dark')->assertNoJavaScriptErrors();
});

it('opens the Wiki link picker in the article editor on phone and desktop', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('knowledgeItems.update.*', 'web'));
    $item = KnowledgeItem::factory()->withoutAudiences()->create();
    KnowledgeItem::factory()->withoutAudiences()->create();
    $page = loginAsAdmin($user, route('knowledgeItems.edit', $item, absolute: false), 390, 844);
    $page->click('[data-testid=step-description] button[aria-label="Nuoroda"]')
        ->assertPresent('[data-testid=link-tab-wiki]')->click('[data-testid=link-tab-wiki]')
        ->assertPresent('[data-testid=link-wiki-panel]')->assertNoJavaScriptErrors()
        ->screenshot(fullPage: false, filename: 'wiki-link-dialog-phone');
    $page->resize(1440, 900)->screenshot(fullPage: false, filename: 'wiki-link-dialog-desktop');
    $page->script('document.documentElement.classList.add("dark")');
    $page->screenshot(fullPage: false, filename: 'wiki-link-dialog-dark')->assertNoJavaScriptErrors();
});
