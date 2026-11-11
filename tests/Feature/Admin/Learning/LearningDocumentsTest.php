<?php

use App\Enums\DocumentStatus;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStepKind;
use App\Models\Document;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\LearningProgress;
use App\Models\LearningStep;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('documents contains only knowledge in the members resolved path with progress and sources', function (): void {
    $this->freezeTime();
    $member = makeUser(Tenant::query()->firstOrFail());
    LearningStep::query()->update(['is_active' => false]);
    $document = Document::factory()->create(['status' => DocumentStatus::Published, 'anonymous_url' => 'https://example.test/guide', 'sharepoint_modified_at' => null]);
    $knowledge = KnowledgeItem::factory()->create();
    $knowledge->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    LearningProgress::factory()->for($member)->create([
        'progressable_type' => $knowledge->getMorphClass(), 'progressable_id' => $knowledge->id, 'status' => LearningProgressStatus::Done, 'completed_at' => now(),
    ]);
    LearningStep::factory()->create(['kind' => LearningStepKind::Action]);
    KnowledgeItem::factory()->forTenant(Tenant::factory()->create())->create();
    KnowledgeItem::factory()->create(['is_active' => false]);

    asUser($member)->get(route('knowledgeBase.show'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/ShowKnowledgeBase')
        ->has('knowledge.items', 1)
        ->where('knowledge.items.0.id', $knowledge->id)
        ->whereType('knowledge.items.0.audienceLabel', 'string')
        ->whereType('knowledge.items.0.title', 'string')
        ->whereType('knowledge.items.0.description', 'string')
        ->where('knowledge.items.0.state', 'done')
        ->where('knowledge.items.0.source.url', route('documents.show', $document))
        ->missing('learningNavigation'));
});

test('hidden sources open through the knowledge item and the path can be empty', function (): void {
    $member = makeUser(Tenant::query()->firstOrFail());
    LearningStep::query()->update(['is_active' => false]);
    $hidden = Document::factory()->create(['status' => DocumentStatus::Hidden, 'anonymous_url' => null]);
    $step = KnowledgeItem::factory()->create();
    $step->sources()->create(['locale' => 'lt', 'document_id' => $hidden->id]);

    asUser($member)->get(route('knowledgeBase.show'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('knowledge.items', 1)->where('knowledge.items.0.source.url', route('knowledgeItems.openSource', $step))->where('knowledge.items.0.source.private', true));

    $step->update(['is_active' => false]);
    asUser($member)->get(route('knowledgeBase.show'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('knowledge.items', [])->where('knowledge.sections', []));
});

test('document payload includes only relevant sections and each documents section', function (): void {
    $member = makeUser(Tenant::query()->firstOrFail());
    LearningStep::query()->update(['is_active' => false]);
    $section = KnowledgeSection::factory()->create(['order' => 4]);
    KnowledgeSection::factory()->create(['title' => ['lt' => 'Kita', 'en' => 'Other']]);
    $document = KnowledgeItem::factory()->create(['knowledge_section_id' => $section->id]);
    $ungrouped = KnowledgeItem::factory()->create();
    $response = asUser($member)->get(route('knowledgeBase.show'))->assertOk();
    $props = $response->viewData('page')['props'];

    expect($props['knowledge']['sections'])->toBe([['id' => $section->id, 'title' => 'Įvadiniai mokymai', 'description' => 'Mokymų dokumentai', 'order' => 4]]);
    $documents = collect($props['knowledge']['items'])->keyBy('id');
    expect($documents[$document->id]['section'])->toBe(['id' => $section->id, 'title' => 'Įvadiniai mokymai']);
    expect($documents[$ungrouped->id]['section'])->toBeNull();
});
