<?php

use App\Actions\ResolveLearningPath;
use App\Enums\DocumentStatus;
use App\Enums\LearningStepKind;
use App\Models\Document;
use App\Models\InstitutionType;
use App\Models\LearningStep;
use App\Models\LearningStepProgress;
use App\Models\Permission;
use App\Models\Tenant;
use App\Services\Learning\ResolvedLearningStep;
use App\Services\LearningPathPresenter;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sources fall back to Lithuanian and expose published document links', function (): void {
    $viewer = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->create();
    $document = Document::factory()->create(['status' => DocumentStatus::Published, 'anonymous_url' => 'https://example.test/shared']);
    $step->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    $step->load(['sources.document', 'section', 'audiences'])->loadCount('comments');
    $data = LearningPathPresenter::step(new ResolvedLearningStep($step), $viewer, 'en');

    expect($data['source']['url'])->toBe(route('documents.show', $document->id));
    expect($data['source']['documentId'])->toBe($document->id);
});

test('hidden sources expose no link to members and an edit link to curators', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $viewer = makeUser($tenant);
    $step = LearningStep::factory()->create();
    $document = Document::factory()->create(['status' => DocumentStatus::Hidden, 'anonymous_url' => null]);
    $step->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    $step->load(['sources.document', 'section', 'audiences'])->loadCount('comments');
    $item = new ResolvedLearningStep($step);
    expect(LearningPathPresenter::step($item, $viewer, 'lt')['source']['url'])->toBeNull();
    $viewer->givePermissionTo(Permission::findOrCreate('documents.update.*', 'web'));
    app(ModelAuthorizer::class)->resetCache($viewer);

    expect(LearningPathPresenter::step($item, $viewer, 'lt')['source']['url'])->toBe(route('documents.edit', $document->id));
});

test('system text is localized and raw URLs preserve the source contract', function (): void {
    $viewer = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::query()->where('key', 'check_access')->with('sources')->withCount('comments')->firstOrFail();
    $data = LearningPathPresenter::step(new ResolvedLearningStep($step), $viewer, 'en');
    expect($data['title'])->toBe(__('learning.system_steps.check_access.title', [], 'en'));
    expect($data['actionUrl'])->toBe(route('profile.roles'));
    $custom = LearningStep::factory()->create();
    $custom->sources()->create(['locale' => 'en', 'url' => 'https://example.test/learn']);
    $custom->load('sources.document')->loadCount('comments');
    expect(LearningPathPresenter::step(new ResolvedLearningStep($custom), $viewer, 'lt')['source'])->toBe([
        'type' => 'url', 'url' => 'https://example.test/learn', 'documentId' => null, 'documentStatus' => null, 'title' => null,
    ]);
});

test('automatic tracking is exposed before a system step is completed', function (): void {
    $viewer = makeUser(Tenant::query()->firstOrFail());
    foreach (['profile_photo' => true, 'notification_settings' => true, 'check_access' => false] as $key => $tracked) {
        $step = LearningStep::query()->where('key', $key)->firstOrFail();
        $data = LearningPathPresenter::step(new ResolvedLearningStep($step), $viewer, 'lt');
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
    $essential = LearningStep::factory()->audience(['importance' => 'essential'])->create();
    $done = LearningStep::factory()->audience(['importance' => 'essential'])->create();
    LearningStep::factory()->audience(['importance' => 'recommended'])->create();
    LearningStepProgress::factory()->for($user)->create(['learning_step_id' => $done->id]);
    $prep = LearningStep::factory()->meetingPrep($type)->create();
    $path = ResolveLearningPath::execute($user);
    $payload = LearningPathPresenter::path($path, 'lt');

    expect(collect($payload['stages'])->flatMap(fn (array $stage) => $stage['steps'])->pluck('id')->all())->toBe([$action->id]);
    expect($payload['totals']['allTotal'])->toBe(1);
    expect($payload['documents'])->toMatchArray(['essentialDone' => 1, 'essentialTotal' => 2, 'done' => 1, 'total' => 3]);
    expect($payload['documents']['nextDocument']['id'])->toBe($essential->id)
        ->and($payload['documents'])->not->toHaveKey('next');
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
    LearningStep::factory()->audience(['importance' => 'optional'])->create();

    $payload = LearningPathPresenter::path(ResolveLearningPath::execute($user), 'lt');

    expect($payload['documents']['total'])->toBe(1)
        ->and($payload['documents']['nextDocument'])->toBeNull();
});
