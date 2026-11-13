<?php

use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

test('the term map links active terms once per pair and leaves out material and inactive articles', function (): void {
    $topic = KnowledgeSection::factory()->create();
    $council = KnowledgeItem::factory()->create(['knowledge_section_id' => $topic->id]);
    $senate = KnowledgeItem::factory()->create(['knowledge_section_id' => $topic->id]);
    $alone = KnowledgeItem::factory()->create();
    $material = KnowledgeItem::factory()->material()->create();
    $retired = KnowledgeItem::factory()->create(['is_active' => false]);
    $council->outgoingLinks()->attach([$senate->id, $material->id, $retired->id]);
    $senate->outgoingLinks()->attach([$council->id]);

    $degrees = [$council->id => 1, $senate->id => 1, $alone->id => 0];

    asUser(makeUser(Tenant::query()->firstOrFail()))->get(route('knowledgeBase.graph'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Knowledge/ShowKnowledgeGraph')
            ->has('graph.nodes', 3)
            ->has('graph.edges', 1)
            ->where('graph.edges.0', fn ($edge) => collect($edge)->sort()->values()->all() === collect([$council->id, $senate->id])->sort()->values()->all())
            ->where('graph.nodes', fn ($nodes) => collect($nodes)->mapWithKeys(fn ($node) => [$node['id'] => $node['degree']])->all() == $degrees)
            ->has('graph.sections', 1)
            ->where('graph.sections.0.id', $topic->id));
});
