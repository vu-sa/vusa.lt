<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\LearningStep;
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
    $step->load(['sources.document', 'institutionType', 'dutyType', 'tenant'])->loadCount('comments');
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
    $step->load(['sources.document', 'institutionType', 'dutyType', 'tenant'])->loadCount('comments');
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
