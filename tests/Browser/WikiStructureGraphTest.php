<?php

use App\Models\KnowledgeItem;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('shows an article in its structure graph, with other nodes leading to their articles', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('knowledgeItems.update.*', 'web'));
    $senate = KnowledgeItem::factory()->withoutAudiences()->create(['title' => ['lt' => 'Senatas', 'en' => 'Senate']]);
    $senate->structureNodes()->create(['graph' => 'vu', 'node' => 'node-senatas']);
    $council = KnowledgeItem::factory()->withoutAudiences()->create(['title' => ['lt' => 'Taryba', 'en' => 'Council']]);
    $council->structureNodes()->create(['graph' => 'vu', 'node' => 'node-taryba']);

    $page = loginAsAdmin($user, route('knowledgeItems.show', $senate, absolute: false), 1440, 1000);
    $page->assertVisible('[data-slot=article-structure] .vue-flow__node[data-id=node-senatas]')
        ->assertPresent('.vue-flow__node[data-id=node-taryba] [data-testid=structure-node-linked]')
        ->assertNoJavaScriptErrors()
        ->screenshotElement('[data-slot=article-structure]', filename: 'wiki-structure-article');
    $page->click('.vue-flow__node[data-id=node-taryba] button')
        ->assertAttribute('[data-testid=structure-node-article]', 'href', route('knowledgeItems.show', $council))
        ->assertPresent('.vue-flow__node.is-faded[data-id=node-studentai]')
        ->assertNotPresent('.vue-flow__node.is-faded[data-id=node-taryba]')
        ->screenshotElement('[data-slot=article-structure]', filename: 'wiki-structure-node-open');
    // The details follow the pointer; the click only keeps the focus fade.
    $page->hover('#article-structure')->wait(0.5)
        ->assertNotPresent('[data-testid=structure-node-article]')
        ->assertPresent('.vue-flow__node.is-faded[data-id=node-studentai]');
    $page->resize(390, 844)->assertNoJavaScriptErrors()->screenshotElement('[data-slot=article-structure]', filename: 'wiki-structure-phone');
});

it('lets an editor pick a graph and a node, previewing the node highlighted', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('knowledgeItems.update.*', 'web'));
    $item = KnowledgeItem::factory()->withoutAudiences()->create();

    $page = loginAsAdmin($user, route('knowledgeItems.edit', $item, absolute: false), 1440, 1000);
    $page->click('[data-testid=structure-add]')
        ->select('[data-testid=structure-graph-0]', 'vusa-structure')
        ->select('[data-testid=structure-node-0]', 'node-parlamentas')
        ->click('[data-testid=structure-add]')
        ->assertVisible('[data-testid=structure-place-1]')
        ->assertVisible('[data-testid=structure-place-0] .vue-flow__node[data-id=node-parlamentas]')
        ->assertNoJavaScriptErrors()
        ->screenshotElement('[data-slot=structure-field]', filename: 'wiki-structure-form');
});
