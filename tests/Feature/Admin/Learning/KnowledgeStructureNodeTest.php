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

function placeOn(KnowledgeItem $item, string $graph, ?string $node = null): KnowledgeItem
{
    $item->structureNodes()->create(['graph' => $graph, 'node' => $node, 'order' => $item->structureNodes()->count()]);

    return $item;
}

test('an article is placed on nodes of several graphs, or on a whole graph, in order', function (): void {
    $item = KnowledgeItem::factory()->create(['tenant_id' => null]);

    asUser(structureEditor())->put(route('knowledgeItems.update', $item), structurePayload(['structure' => [
        ['graph' => 'vu', 'node' => 'node-senatas'],
        ['graph' => 'am', 'node' => null],
    ]]))->assertValid();

    expect($item->structureNodes()->get(['graph', 'node', 'order'])->toArray())->toBe([
        ['graph' => 'vu', 'node' => 'node-senatas', 'order' => 0],
        ['graph' => 'am', 'node' => null, 'order' => 1],
    ]);

    asUser(structureEditor())->put(route('knowledgeItems.update', $item), structurePayload(['structure' => []]))->assertValid();
    expect($item->structureNodes()->count())->toBe(0);
});

test('a node must exist in its graph, belongs to one article, and an article sits once per graph', function (): void {
    placeOn(KnowledgeItem::factory()->create(), 'vusa-structure', 'node-taryba');
    $item = KnowledgeItem::factory()->create(['tenant_id' => null]);
    $editor = structureEditor();
    $put = fn (array $structure) => asUser($editor)->put(route('knowledgeItems.update', $item), structurePayload(['structure' => $structure]));

    $put([['graph' => 'nope']])->assertInvalid('structure.0.graph');
    $put([['graph' => 'vusa-structure', 'node' => 'node-missing']])->assertInvalid('structure.0.node');
    $put([['graph' => 'vusa-structure', 'node' => 'node-taryba']])->assertInvalid('structure.0.node');
    $put([['graph' => 'vu'], ['graph' => 'vu', 'node' => 'node-senatas']])->assertInvalid('structure.1.graph');
});

test('an article keeps its own node when saved again', function (): void {
    $item = placeOn(KnowledgeItem::factory()->create(['tenant_id' => null]), 'vu', 'node-senatas');

    asUser(structureEditor())->put(route('knowledgeItems.update', $item), structurePayload(['structure' => [['graph' => 'vu', 'node' => 'node-senatas']]]))->assertValid();
});

test('the article page lists each graph with the articles its other nodes stand for', function (): void {
    $item = placeOn(placeOn(KnowledgeItem::factory()->create(), 'vusa-structure', 'node-taryba'), 'vu');
    $other = placeOn(KnowledgeItem::factory()->create(['title' => ['lt' => 'Parlamentas', 'en' => 'Parliament']]), 'vusa-structure', 'node-parlamentas');
    placeOn(KnowledgeItem::factory()->create(['is_active' => false]), 'vusa-structure', 'node-cb');
    placeOn(KnowledgeItem::factory()->create(), 'vu', 'node-rektorius');

    $structures = KnowledgeBasePresenter::pageContext($item, 'en')['structures'];

    expect(array_column($structures, 'graph'))->toBe(['vusa-structure', 'vu'])
        ->and($structures[0]['node'])->toBe('node-taryba')
        ->and(array_keys($structures[0]['links']))->toEqualCanonicalizing(['node-taryba', 'node-parlamentas'])
        ->and($structures[0]['links']['node-parlamentas'])->toBe(['title' => 'Parliament', 'url' => route('knowledgeItems.show', $other)])
        ->and($structures[1]['node'])->toBeNull()
        ->and(array_keys($structures[1]['links']))->toBe(['node-rektorius']);
});
