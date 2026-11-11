<?php

use App\Enums\KnowledgeItemKind;
use App\Enums\KnowledgeSectionKind;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\KnowledgeBasePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function wikiEditor(): User
{
    $user = makeUser(Tenant::query()->firstOrFail());
    foreach (['create', 'update'] as $ability) {
        $user->givePermissionTo(Permission::findOrCreate("knowledgeItems.{$ability}.*", 'web'));
    }

    return $user;
}

function articlePayload(array $overrides = []): array
{
    return [
        'title' => ['lt' => 'Kvorumas', 'en' => 'Quorum'],
        'summary' => ['lt' => '<strong>Kvorumas</strong> – mažiausias narių skaičius.', 'en' => ''],
        'description' => ['lt' => '', 'en' => ''],
        'is_active' => true,
        ...$overrides,
    ];
}

test('a term goes in a topic and material in a collection', function (): void {
    $user = wikiEditor();
    $topic = KnowledgeSection::factory()->create();
    $collection = KnowledgeSection::factory()->collection('2026-09-20')->create();

    asUser($user)->post(route('knowledgeItems.store'), articlePayload(['kind' => 'term', 'knowledge_section_id' => $collection->id]))->assertInvalid('knowledge_section_id');
    asUser($user)->post(route('knowledgeItems.store'), articlePayload(['kind' => 'material', 'knowledge_section_id' => $topic->id]))->assertInvalid('knowledge_section_id');
    asUser($user)->post(route('knowledgeItems.store'), articlePayload(['kind' => 'material', 'knowledge_section_id' => $collection->id]))->assertRedirect();

    expect(KnowledgeItem::query()->sole())->kind->toBe(KnowledgeItemKind::Material)->knowledge_section_id->toBe($collection->id);
});

test('a section with articles keeps its kind, and only a collection has a date', function (): void {
    $user = wikiEditor();
    $section = KnowledgeSection::factory()->create();
    KnowledgeItem::factory()->create(['knowledge_section_id' => $section->id]);
    $payload = ['title' => ['lt' => 'Tema', 'en' => 'Topic']];

    asUser($user)->patch(route('knowledgeSections.update', $section), [...$payload, 'kind' => 'collection'])->assertInvalid('kind');
    asUser($user)->patch(route('knowledgeSections.update', $section), [...$payload, 'date' => '2026-09-20'])->assertInvalid('date');
    asUser($user)->post(route('knowledgeSections.store'), [...$payload, 'kind' => 'collection', 'date' => '2026-09-20'])->assertRedirect();

    expect(KnowledgeSection::query()->where('kind', KnowledgeSectionKind::Collection)->sole()->date->toDateString())->toBe('2026-09-20');
});

test('the summary keeps only inline marks and its length counts text, not tags', function (): void {
    $user = wikiEditor();
    $item = KnowledgeItem::factory()->create();
    $bold = '<strong>'.str_repeat('a', 10).'</strong>'.str_repeat('b', 190);

    asUser($user)->put(route('knowledgeItems.update', $item), articlePayload(['summary' => ['lt' => "<p>{$bold}<script>x</script><a href=\"https://x.test\">link</a></p>"]]))->assertRedirect();
    expect($item->fresh()->getTranslation('summary', 'lt'))->toBe($bold);

    asUser($user)->put(route('knowledgeItems.update', $item), articlePayload(['summary' => ['lt' => "<strong>a</strong>{$bold}"]]))->assertInvalid('summary.lt');
});

test('articles are ordered on their section page by its editors only', function (): void {
    $user = wikiEditor();
    $section = KnowledgeSection::factory()->create();
    [$first, $second] = KnowledgeItem::factory()->count(2)->sequence(['order' => 0], ['order' => 1])->create(['knowledge_section_id' => $section->id]);
    $foreign = KnowledgeItem::factory()->create();

    asUser($user)->get(route('knowledgeSections.show', $section))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/ShowKnowledgeSection')->where('section.id', $section->id)
        ->where('pages.0.id', $first->id)->where('canReorder', true));

    asUser($user)->post(route('knowledgeSections.items.reorder', $section), ['items' => [$second->id, $foreign->id]])->assertInvalid('items');
    asUser($user)->post(route('knowledgeSections.items.reorder', $section), ['items' => [$second->id, $first->id]])->assertRedirect();
    expect($second->fresh()->order)->toBe(0)->and($first->fresh()->order)->toBe(1);

    $reader = makeUser(Tenant::query()->firstOrFail());
    asUser($reader)->get(route('knowledgeSections.show', $section))->assertOk()->assertInertia(fn (Assert $page) => $page->where('canReorder', false));
    asUser($reader)->post(route('knowledgeSections.items.reorder', $section), ['items' => [$first->id, $second->id]])->assertForbidden();
});

test('material may be about an active term, and the term lists it with the viewer\'s unit first', function (): void {
    $user = wikiEditor();
    $tenant = Tenant::query()->firstOrFail();
    $term = KnowledgeItem::factory()->create();
    $otherMaterial = KnowledgeItem::factory()->material()->create(['tenant_id' => Tenant::factory()->create()->id]);
    $ownMaterial = KnowledgeItem::factory()->material()->create(['tenant_id' => $tenant->id]);
    $inactiveTerm = KnowledgeItem::factory()->create(['is_active' => false]);

    foreach ([$otherMaterial, $inactiveTerm] as $notATerm) {
        asUser($user)->put(route('knowledgeItems.subjects.sync', $ownMaterial), ['subjects' => [['type' => 'knowledge_item', 'id' => $notATerm->id]]])->assertInvalid('subjects.0.id');
    }
    foreach ([$otherMaterial, $ownMaterial] as $material) {
        asUser($user)->put(route('knowledgeItems.subjects.sync', $material), ['subjects' => [['type' => 'knowledge_item', 'id' => $term->id]]])->assertRedirect();
    }

    $materials = KnowledgeBasePresenter::materials($term, 'lt', $user);
    expect(array_column($materials, 'id'))->toBe([$ownMaterial->id, $otherMaterial->id])
        ->and(array_column($materials, 'mine'))->toBe([true, false]);
});

test('Rodyklė A–Ž lists terms only, while search covers material too', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $term = KnowledgeItem::factory()->create();
    KnowledgeItem::factory()->material()->create();

    asUser($user)->get(route('knowledgeItems.alphabet'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('index', 1)->where('index.0.id', $term->id));
    asUser($user)->get(route('knowledgeBase.show'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('index', 2));
});
