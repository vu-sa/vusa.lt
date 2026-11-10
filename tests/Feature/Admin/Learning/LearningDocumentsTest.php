<?php

use App\Enums\DocumentStatus;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStepKind;
use App\Models\Document;
use App\Models\GlossaryTerm;
use App\Models\LearningSection;
use App\Models\LearningStep;
use App\Models\LearningStepProgress;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('documents contains only knowledge in the members resolved path with progress sources and glossary', function (): void {
    $this->freezeTime();
    $member = makeUser(Tenant::query()->firstOrFail());
    LearningStep::query()->update(['is_active' => false]);
    $document = Document::factory()->create(['status' => DocumentStatus::Published, 'anonymous_url' => 'https://example.test/guide', 'sharepoint_modified_at' => null]);
    $knowledge = LearningStep::factory()->create(['kind' => LearningStepKind::Knowledge]);
    $knowledge->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    $term = GlossaryTerm::factory()->create(['term' => ['lt' => 'Kvorumas', 'en' => 'Quorum']]);
    $knowledge->glossaryTerms()->attach($term);
    LearningStepProgress::factory()->for($member)->create([
        'learning_step_id' => $knowledge->id, 'status' => LearningProgressStatus::Done, 'completed_at' => now(),
    ]);
    LearningStep::factory()->create(['kind' => LearningStepKind::Action]);
    LearningStep::factory()->forTenant(Tenant::factory()->create())->create();
    LearningStep::factory()->create(['is_active' => false]);

    asUser($member)->get(route('learningPath.documents'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/ShowLearningDocuments')
        ->has('documents', 1)
        ->where('documents.0.id', $knowledge->id)
        ->where('documents.0.kind', 'knowledge')
        ->whereType('documents.0.audienceLabel', 'string')
        ->whereType('documents.0.title', 'string')
        ->whereType('documents.0.description', 'string')
        ->where('documents.0.state', 'done')
        ->where('documents.0.source.url', route('documents.show', $document))
        ->where('documents.0.glossaryTerms.0.id', $term->id)
        ->where('learningNavigation.canManage', false));
});

test('documents keeps hidden sources unlinked and supports an empty path', function (): void {
    $member = makeUser(Tenant::query()->firstOrFail());
    LearningStep::query()->update(['is_active' => false]);
    $hidden = Document::factory()->create(['status' => DocumentStatus::Hidden, 'anonymous_url' => null]);
    $step = LearningStep::factory()->create();
    $step->sources()->create(['locale' => 'lt', 'document_id' => $hidden->id]);

    asUser($member)->get(route('learningPath.documents'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('documents', 1)->where('documents.0.source.url', null));

    $step->update(['is_active' => false]);
    asUser($member)->get(route('learningPath.documents'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('documents', []));
});

test('document payload includes only relevant sections and each documents section', function (): void {
    $member = makeUser(Tenant::query()->firstOrFail());
    LearningStep::query()->update(['is_active' => false]);
    $section = LearningSection::factory()->create(['order' => 4]);
    LearningSection::factory()->create(['title' => ['lt' => 'Kita', 'en' => 'Other']]);
    $document = LearningStep::factory()->create(['learning_section_id' => $section->id]);
    $ungrouped = LearningStep::factory()->create();
    $response = asUser($member)->get(route('learningPath.documents'))->assertOk();
    $props = $response->viewData('page')['props'];

    expect($props['sections'])->toBe([['id' => $section->id, 'title' => 'Įvadiniai mokymai', 'description' => 'Mokymų dokumentai', 'order' => 4]]);
    $documents = collect($props['documents'])->keyBy('id');
    expect($documents[$document->id]['section'])->toBe(['id' => $section->id, 'title' => 'Įvadiniai mokymai']);
    expect($documents[$ungrouped->id]['section'])->toBeNull();
});
