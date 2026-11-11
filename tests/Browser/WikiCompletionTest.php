<?php

use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('lets a reader complete an off-path article in the built app', function () {
    $user = makeUser(Tenant::query()->firstOrFail());
    $item = KnowledgeItem::factory()->withoutAudiences()->create();
    $page = loginAsAdmin($user, route('knowledgeItems.show', $item, absolute: false), 390, 844);
    $page->assertPresent('[data-testid=step-checkbox]')->assertNoJavaScriptErrors()
        ->screenshot(filename: 'wiki-article-phone');
    $page->click('[data-testid=step-checkbox]')->assertAttribute('[data-testid=step-checkbox]', 'aria-checked', 'true');
    foreach ([820, 1180, 1440] as $width) {
        $page->resize($width, 900)->screenshot(filename: 'wiki-article-'.$width);
    }
    navigateTo($page, route('knowledgeItems.show', $item, absolute: false));
    $page->assertAttribute('[data-testid=step-checkbox]', 'aria-checked', 'true')->assertNoJavaScriptErrors();
});

it('opens the section manager and bilingual editor in the built app', function () {
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

it('opens the Wiki link picker in the article editor on phone and desktop', function () {
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
