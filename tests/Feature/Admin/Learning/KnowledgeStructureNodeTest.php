<?php

use App\Models\KnowledgeItem;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\KnowledgeBasePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function structureEditor(): User
{
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('knowledgeItems.update.*', 'web'));

    return $user;
}

function structurePayload(array $overrides = []): array
{
    return [
        'title' => ['lt' => 'Senatas', 'en' => 'Senate'],
        'summary' => ['lt' => '<strong>Senatas</strong> – akademinės savivaldos organas.', 'en' => ''],
        'description' => ['lt' => '', 'en' => ''],
        'is_active' => true,
        ...$overrides,
    ];
}

test('an article is placed on a node of a structure graph, or on a whole graph', function (): void {
    $item = KnowledgeItem::factory()->create(['tenant_id' => null]);

    asUser(structureEditor())->put(route('knowledgeItems.update', $item), structurePayload(['structure_graph' => 'vusa-structure', 'structure_node' => 'node-taryba']))->assertValid();
    expect($item->refresh())->structure_graph->toBe('vusa-structure')->structure_node->toBe('node-taryba');

    asUser(structureEditor())->put(route('knowledgeItems.update', $item), structurePayload(['structure_graph' => 'vu', 'structure_node' => null]))->assertValid();
    expect($item->refresh())->structure_graph->toBe('vu')->structure_node->toBeNull();
});

test('a node must exist in its graph and belongs to one article only', function (): void {
    KnowledgeItem::factory()->create(['structure_graph' => 'vusa-structure', 'structure_node' => 'node-taryba']);
    $item = KnowledgeItem::factory()->create(['tenant_id' => null]);
    $editor = structureEditor();

    asUser($editor)->put(route('knowledgeItems.update', $item), structurePayload(['structure_graph' => 'nope', 'structure_node' => null]))->assertInvalid('structure_graph');
    asUser($editor)->put(route('knowledgeItems.update', $item), structurePayload(['structure_graph' => 'vusa-structure', 'structure_node' => 'node-missing']))->assertInvalid('structure_node');
    asUser($editor)->put(route('knowledgeItems.update', $item), structurePayload(['structure_graph' => null, 'structure_node' => 'node-cb']))->assertInvalid('structure_graph');
    asUser($editor)->put(route('knowledgeItems.update', $item), structurePayload(['structure_graph' => 'vusa-structure', 'structure_node' => 'node-taryba']))->assertInvalid('structure_node');
});

test('the article page lists the articles other nodes of its graph stand for', function (): void {
    $item = KnowledgeItem::factory()->create(['structure_graph' => 'vusa-structure', 'structure_node' => 'node-taryba']);
    $other = KnowledgeItem::factory()->create(['structure_graph' => 'vusa-structure', 'structure_node' => 'node-parlamentas', 'title' => ['lt' => 'Parlamentas', 'en' => 'Parliament']]);
    KnowledgeItem::factory()->create(['structure_graph' => 'vusa-structure', 'structure_node' => 'node-cb', 'is_active' => false]);
    KnowledgeItem::factory()->create(['structure_graph' => 'vu', 'structure_node' => 'node-rektorius']);

    $structure = KnowledgeBasePresenter::pageContext($item, 'en')['structure'];

    expect($structure['graph'])->toBe('vusa-structure')
        ->and($structure['node'])->toBe('node-taryba')
        ->and(array_keys($structure['links']))->toEqualCanonicalizing(['node-taryba', 'node-parlamentas'])
        ->and($structure['links']['node-parlamentas'])->toBe(['title' => 'Parliament', 'url' => route('knowledgeItems.show', $other)]);
});
