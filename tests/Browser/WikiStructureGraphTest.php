<?php

use App\Models\KnowledgeItem;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('shows an article in its structure graph, with other nodes leading to their articles', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('knowledgeItems.update.*', 'web'));
    $senate = KnowledgeItem::factory()->withoutAudiences()->create(['title' => ['lt' => 'Senatas', 'en' => 'Senate'], 'structure_graph' => 'vu', 'structure_node' => 'node-senatas']);
    $council = KnowledgeItem::factory()->withoutAudiences()->create(['title' => ['lt' => 'Taryba', 'en' => 'Council'], 'structure_graph' => 'vu', 'structure_node' => 'node-taryba']);

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
    $page->resize(390, 844)->assertNoJavaScriptErrors()->screenshotElement('[data-slot=article-structure]', filename: 'wiki-structure-phone');
});

it('lets an editor pick a graph and a node, previewing the node highlighted', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('knowledgeItems.update.*', 'web'));
    $item = KnowledgeItem::factory()->withoutAudiences()->create();

    $page = loginAsAdmin($user, route('knowledgeItems.edit', $item, absolute: false), 1440, 1000);
    $page->select('[data-testid=structure-graph]', 'vusa-structure')
        ->select('[data-testid=structure-node]', 'node-parlamentas')
        ->assertVisible('[data-slot=structure-field] .vue-flow__node[data-id=node-parlamentas]')
        ->assertNoJavaScriptErrors()
        ->screenshotElement('[data-slot=structure-field]', filename: 'wiki-structure-form');
});
