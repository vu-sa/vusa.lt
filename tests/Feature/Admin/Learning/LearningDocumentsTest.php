<?php

use App\Enums\DocumentStatus;
use App\Enums\KnowledgeSectionKind;
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

test('articles for you has its own page with only personal learning data', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $item = KnowledgeItem::factory()->create();

    asUser($user)->get(route('knowledgeBase.mine'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/ShowKnowledgeForYou')
        ->where('knowledge.items.0.id', $item->id)
        ->missing('index')->missing('sections'));
});

test('old personal wiki views redirect to articles for you', function (string $view, string $expected): void {
    $user = makeUser(Tenant::query()->firstOrFail());

    asUser($user)->get(route('knowledgeBase.show', ['view' => $view]))
        ->assertRedirectToRoute('knowledgeBase.mine', ['view' => $expected]);
})->with([
    ['importance', 'importance'],
    ['sections', 'sections'],
    ['mine', 'importance'],
]);

test('wiki separates self-learned articles from path progress and marks completed active articles in both indexes', function (): void {
    $this->freezeSecond();
    $user = makeUser(Tenant::query()->firstOrFail());
    $otherUser = makeUser(Tenant::query()->firstOrFail());
    $selfLearned = KnowledgeItem::factory()->withoutAudiences()->create();
    $pathDone = KnowledgeItem::factory()->create();
    $pathSkipped = KnowledgeItem::factory()->create();
    KnowledgeItem::factory()->create();
    $inactive = KnowledgeItem::factory()->withoutAudiences()->create(['is_active' => false]);
    $otherUsersArticle = KnowledgeItem::factory()->withoutAudiences()->create();
    foreach ([$selfLearned, $pathDone, $inactive] as $item) {
        $item->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()]);
    }
    $pathSkipped->progress()->create(['user_id' => $user->id, 'status' => 'skipped', 'completed_at' => now()]);
    $otherUsersArticle->progress()->create(['user_id' => $otherUser->id, 'status' => 'done', 'completed_at' => now()]);
    $step = LearningStep::factory()->create(['id' => $otherUsersArticle->id]);
    $step->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()]);
    $doneIds = collect([$selfLearned->id, $pathDone->id])->sort()->values()->all();

    $assignedProgress = [
        ['importance' => 'essential', 'done' => 0, 'total' => 0],
        ['importance' => 'recommended', 'done' => 2, 'total' => 3],
        ['importance' => 'optional', 'done' => 0, 'total' => 0],
    ];
    asUser($user)->get(route('knowledgeBase.mine'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('assignedProgress', $assignedProgress)->missing('wikiProgress'));
    asUser($user)->get(route('learningPath.show'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('path.knowledge.priorityProgress', $assignedProgress));

    asUser($user)->get(route('knowledgeBase.show'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('knowledge.selfLearned', 1)
        ->where('knowledge.selfLearned.0.id', $selfLearned->id)
        ->where('knowledge.selfLearned.0.importance', null)
        ->where('knowledge.selfLearned.0.state', 'done')
        ->where('knowledge.selfLearned.0.skippable', false)
        ->where('knowledge.selfLearned.0.completedAt', now()->toIso8601String())
        ->whereType('knowledge.selfLearned.0.title', 'string')
        ->whereType('knowledge.selfLearned.0.insightsCount', 'integer')
        ->where('knowledge.stats', ['pathDone' => 2, 'pathTotal' => 3, 'selfLearned' => 1])
        ->where('doneIds', fn ($ids): bool => $ids->sort()->values()->all() === $doneIds));
    asUser($user)->get(route('knowledgeItems.alphabet'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('doneIds', fn ($ids): bool => $ids->sort()->values()->all() === $doneIds));
});

test('self-learned articles are ordered by latest completion with their section and source payloads', function (): void {
    $this->freezeSecond();
    $user = makeUser(Tenant::query()->firstOrFail());
    $section = KnowledgeSection::factory()->create();
    $older = KnowledgeItem::factory()->withoutAudiences()->create();
    $newer = KnowledgeItem::factory()->withoutAudiences()->create(['knowledge_section_id' => $section->id]);
    $newer->sources()->create(['locale' => 'lt', 'url' => 'https://example.test/self-learning']);
    $older->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()->subDay()]);
    $newer->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()]);

    asUser($user)->get(route('knowledgeBase.show'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('knowledge.selfLearned', 2)
        ->where('knowledge.selfLearned.0.id', $newer->id)
        ->where('knowledge.selfLearned.1.id', $older->id)
        ->where('knowledge.selfLearned.0.section.id', $section->id)
        ->where('knowledge.selfLearned.0.source.url', 'https://example.test/self-learning')
        ->where('knowledge.stats', ['pathDone' => 0, 'pathTotal' => 0, 'selfLearned' => 2]));
});

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

    expect($props['knowledge']['sections'])->toBe([['id' => $section->id, 'kind' => KnowledgeSectionKind::Topic, 'title' => 'Įvadiniai mokymai', 'description' => 'Mokymų dokumentai', 'date' => null, 'order' => 4]]);
    $documents = collect($props['knowledge']['items'])->keyBy('id');
    expect($documents[$document->id]['section'])->toBe(['id' => $section->id, 'title' => 'Įvadiniai mokymai']);
    expect($documents[$ungrouped->id]['section'])->toBeNull();
});

test('updated source clears learned marks across wiki views and off-path records', function (): void {
    $this->freezeSecond();
    $user = makeUser(Tenant::query()->firstOrFail());
    foreach ([true, false] as $inPath) {
        $item = $inPath ? KnowledgeItem::factory()->create() : KnowledgeItem::factory()->withoutAudiences()->create();
        $document = Document::factory()->create(['sharepoint_modified_at' => now()]);
        $item->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
        $item->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()->subDay()]);
        asUser($user)->get(route('knowledgeItems.show', $item))->assertOk()->assertInertia(fn (Assert $page) => $page->where('item.state', 'updated'));
    }
    asUser($user)->get(route('knowledgeBase.show'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('doneIds', [])->where('knowledge.selfLearned', [])->where('knowledge.stats.pathDone', 0));
    asUser($user)->get(route('knowledgeItems.alphabet'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('doneIds', []));
    asUser($user)->get(route('knowledgeBase.mine'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('assignedProgress.1.done', 0)->where('assignedProgress.1.total', 1));
});
