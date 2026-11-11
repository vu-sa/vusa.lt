<?php

use App\Actions\ResolveLearningPath;
use App\Enums\DocumentStatus;
use App\Enums\LearningStepKind;
use App\Models\Comment;
use App\Models\Document;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\LearningProgress;
use App\Models\LearningStep;
use App\Models\Tenant;
use App\Services\KnowledgeBasePresenter;
use App\Services\Learning\ResolvedLearningItem;
use App\Services\LearningPathPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sources fall back to Lithuanian and expose published document links', function (): void {
    $step = KnowledgeItem::factory()->create();
    $document = Document::factory()->create(['status' => DocumentStatus::Published, 'anonymous_url' => 'https://example.test/shared']);
    $step->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    $step->load(['sources.document', 'section', 'audiences'])->loadCount('comments');
    $data = KnowledgeBasePresenter::item(new ResolvedLearningItem($step), 'en');

    expect($data['source']['url'])->toBe(route('documents.show', $document->id));
    expect($data['source']['documentId'])->toBe($document->id);
});

test('hidden sources open through the knowledge item and show its thumbnail', function (): void {
    $step = KnowledgeItem::factory()->create();
    $document = Document::factory()->create(['status' => DocumentStatus::Hidden, 'anonymous_url' => null, 'thumbnail_path' => 'document-thumbnails/1.webp', 'thumbnail_etag' => 'etag-1']);
    $step->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    $step->load(['sources.document', 'section', 'audiences'])->loadCount('comments');
    $source = KnowledgeBasePresenter::item(new ResolvedLearningItem($step), 'lt')['source'];

    expect($source['url'])->toBe(route('knowledgeItems.openSource', $step))
        ->and($source['private'])->toBeTrue()
        ->and($source['thumbnailUrl'])->toStartWith(route('knowledgeItems.thumbnail', $step).'?v=');
});

test('system text is localized and raw URLs preserve the source contract', function (): void {
    $viewer = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::query()->where('key', 'install_app')->firstOrFail();
    $data = LearningPathPresenter::step(new ResolvedLearningItem($step), $viewer, 'en');
    expect($data['title'])->toBe(__('learning.system_steps.install_app.title', [], 'en'));
    expect($data['actionLabel'])->toBe(__('learning.system_steps.install_app.action', [], 'en'));
    $custom = KnowledgeItem::factory()->create();
    $custom->sources()->create(['locale' => 'en', 'url' => 'https://example.test/learn']);
    $custom->load('sources.document')->loadCount('comments');
    expect(KnowledgeBasePresenter::item(new ResolvedLearningItem($custom), 'lt')['source'])->toBe([
        'type' => 'url', 'url' => 'https://example.test/learn', 'documentId' => null, 'documentStatus' => null, 'title' => null, 'private' => false, 'thumbnailUrl' => null,
    ]);
});

test('an authored task carries its own button text and link per language', function (): void {
    $viewer = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->create([
        'kind' => LearningStepKind::Action,
        'action_label' => ['lt' => 'Nustatyti pranešimus', 'en' => 'Set notifications'],
        'action_url' => ['lt' => '/mano/profile/notifications'],
    ]);

    $data = LearningPathPresenter::step(new ResolvedLearningItem($step), $viewer, 'en');
    expect($data['actionLabel'])->toBe('Set notifications')
        ->and($data['actionUrl'])->toBe('/mano/profile/notifications')
        ->and($data['automaticallyTracked'])->toBeFalse();
});

test('the insights count leaves out deleted comments', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $knowledgeItem = KnowledgeItem::factory()->create();
    Comment::factory()->for($knowledgeItem, 'commentable')->count(2)->create();
    Comment::factory()->for($knowledgeItem, 'commentable')->create()->erase();

    $item = ResolveLearningPath::execute($user)->knowledge->firstWhere('item.id', $knowledgeItem->id);
    expect(KnowledgeBasePresenter::item($item, 'lt')['insightsCount'])->toBe(2);
});

test('system descriptions arrive as escaped paragraphs', function (): void {
    $viewer = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::query()->where('key', 'install_app')->firstOrFail();

    $data = LearningPathPresenter::step(new ResolvedLearningItem($step), $viewer, 'lt');
    expect($data['description'])->toBe('<p>'.e(__('learning.system_steps.install_app.description', [], 'lt')).'</p>');
});

test('automatic tracking is exposed before a system step is completed', function (): void {
    $viewer = makeUser(Tenant::query()->firstOrFail());
    foreach (['browser_push' => true, 'record_first_meeting' => true, 'install_app' => false] as $key => $tracked) {
        $step = LearningStep::query()->where('key', $key)->firstOrFail();
        $data = LearningPathPresenter::step(new ResolvedLearningItem($step), $viewer, 'lt');
        expect($data['automaticallyTracked'])->toBe($tracked)
            ->and($data['autoCompleted'])->toBeFalse();
    }
});

test('path separates document progress and groups meeting preparation by the winning audience', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    LearningStep::query()->update(['is_active' => false]);
    $duty = $user->authorization_duties()->firstOrFail();
    $type = InstitutionType::factory()->create();
    $duty->institution->types()->attach($type);
    $action = LearningStep::factory()->create(['kind' => LearningStepKind::Action]);
    $essential = KnowledgeItem::factory()->audience(['importance' => 'essential'])->create();
    $done = KnowledgeItem::factory()->audience(['importance' => 'essential'])->create();
    KnowledgeItem::factory()->audience(['importance' => 'recommended'])->create();
    LearningProgress::factory()->for($user)->create(['progressable_type' => $done->getMorphClass(), 'progressable_id' => $done->id]);
    $prep = LearningStep::factory()->meetingPrep($type)->create();
    $path = ResolveLearningPath::execute($user);
    $payload = LearningPathPresenter::path($path, 'lt');

    expect(collect($payload['stages'])->flatMap(fn (array $stage) => $stage['steps'])->pluck('id')->all())->toBe([$action->id]);
    expect($payload['totals']['allTotal'])->toBe(1);
    expect($payload['knowledge'])->toMatchArray(['essentialDone' => 1, 'essentialTotal' => 2, 'done' => 1, 'total' => 3]);
    expect($payload['knowledge']['nextItem']['id'])->toBe($essential->id)
        ->and($payload['knowledge'])->not->toHaveKey('next');
    expect($payload['meetingPrep'][0]['audience'])->toBe(['id' => 'institution_type:'.$type->id.':', 'label' => $type->getTranslation('title', 'lt')]);
    expect($payload['meetingPrep'][0]['steps'][0]['id'])->toBe($prep->id);
});

test('meeting prep steps for the same institution type form one group', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    LearningStep::query()->update(['is_active' => false]);
    $type = InstitutionType::factory()->create();
    $user->authorization_duties()->firstOrFail()->institution->types()->attach($type);
    $first = LearningStep::factory()->meetingPrep($type)->create(['order' => 1]);
    $second = LearningStep::factory()->meetingPrep($type)->create(['order' => 2]);
    $path = ResolveLearningPath::execute($user);
    $key = 'institution_type:'.$type->id.':';

    expect($path->meetingPrep->pluck('audienceKey')->all())->toBe([$key, $key]);
    $payload = LearningPathPresenter::path($path, 'lt');

    expect($payload['meetingPrep'])->toHaveCount(1)
        ->and($payload['meetingPrep'][0]['audience']['id'])->toBe($key)
        ->and(array_column($payload['meetingPrep'][0]['steps'], 'id'))->toBe([$first->id, $second->id]);
});

test('the next document is null when nothing remains to recommend', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    LearningStep::query()->update(['is_active' => false]);
    KnowledgeItem::factory()->audience(['importance' => 'optional'])->create();

    $payload = LearningPathPresenter::path(ResolveLearningPath::execute($user), 'lt');

    expect($payload['knowledge']['total'])->toBe(1)
        ->and($payload['knowledge']['nextItem'])->toBeNull();
});
