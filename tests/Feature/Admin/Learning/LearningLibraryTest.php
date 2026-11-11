<?php

use App\Enums\DocumentStatus;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\DutyType;
use App\Models\GlossaryTerm;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeItemSource;
use App\Models\LearningStep;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('library payload includes curator flags for expired document, updated document and missing source', function (): void {
    $this->freezeTime();
    $tenant = Tenant::query()->firstOrFail();
    $curator = makeUser($tenant);
    $curator->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));

    $expiredDoc = Document::factory()->create([
        'effective_date' => today()->subYear(),
        'expiration_date' => today()->subDay(),
        'sharepoint_modified_at' => null,
    ]);
    $expiredStep = KnowledgeItem::factory()->create([
        'reviewed_at' => now(),
    ]);
    KnowledgeItemSource::factory()->for($expiredStep, 'item')->create([
        'locale' => 'lt',
        'document_id' => $expiredDoc->id,
        'url' => null,
    ]);

    $updatedDoc = Document::factory()->create([
        'effective_date' => null,
        'expiration_date' => null,
        'sharepoint_modified_at' => now()->subHour(),
    ]);
    $updatedStep = KnowledgeItem::factory()->create([
        'reviewed_at' => now()->subDay(),
    ]);
    KnowledgeItemSource::factory()->for($updatedStep, 'item')->create([
        'locale' => 'lt',
        'document_id' => $updatedDoc->id,
        'url' => null,
    ]);

    $missingSourceStep = KnowledgeItem::factory()->create([
    ]);

    $response = asUser($curator)->get(route('knowledgeItems.index'))->assertOk();
    $steps = collect($response->viewData('page')['props']['items'])->keyBy('id');

    expect($steps[$expiredStep->id]['canUpdate'])->toBeTrue()
        ->and($steps[$expiredStep->id]['flags'])->toContain('expired_document')
        ->and($steps[$updatedStep->id]['flags'])->toContain('updated_document')
        ->and($steps[$missingSourceStep->id]['flags'])->toContain('missing_source');

    $member = makeUser($tenant);
    asUser($member)->get(route('knowledgeItems.index'))->assertForbidden();
});

test('create and edit pages offer audience types with managed tenants and identify global curators', function (): void {
    $ownTenant = Tenant::query()->firstOrFail();
    $otherTenant = Tenant::factory()->create();
    InstitutionType::factory()->create();
    DutyType::factory()->create();

    $padalinysCurator = makeUser($ownTenant);
    $padalinysCurator->givePermissionTo(Permission::findOrCreate('learningSteps.create.padalinys', 'web'));
    $padalinysCurator->givePermissionTo(Permission::findOrCreate('learningSteps.update.padalinys', 'web'));

    $tenantStep = LearningStep::factory()->forTenant($ownTenant)->create();

    asUser($padalinysCurator)->get(route('learningSteps.create'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/CreateLearningStep')
        ->where('canTargetEverywhere', false)
        ->has('audienceTypes', 6)
        ->has('tenants', 1)
        ->where('tenants.0.id', $ownTenant->id)
        ->has('institutionTypes')->has('dutyTypes'));

    asUser($padalinysCurator)->get(route('learningSteps.edit', $tenantStep))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/EditLearningStep')
        ->where('canTargetEverywhere', false)
        ->has('audienceTypes', 6)
        ->has('tenants', 1)
        ->where('tenants.0.id', $ownTenant->id));

    $globalCurator = makeUser($ownTenant);
    $globalCurator->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $globalCurator->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));

    $createProps = asUser($globalCurator)->get(route('learningSteps.create'))->assertOk()->viewData('page')['props'];
    expect($createProps['canTargetEverywhere'])->toBeTrue()
        ->and($createProps['audienceTypes'])->not->toContain('purpose')
        ->and(collect($createProps['tenants'])->pluck('id')->all())->toContain($ownTenant->id, $otherTenant->id)
        ->and($createProps['institutionTypes'])->not->toBeEmpty()
        ->and($createProps['dutyTypes'])->not->toBeEmpty();
});

test('store and update set reviewed_at to now', function (): void {
    $this->freezeTime();
    $tenant = Tenant::query()->firstOrFail();
    $curator = makeUser($tenant);
    $curator->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $curator->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));

    asUser($curator)->post(route('learningSteps.store'), [
        'kind' => LearningStepKind::Action->value,
        'audiences' => [['type' => 'everyone', 'stage' => LearningStage::BeforeStart->value, 'importance' => LearningImportance::Essential->value]],
        'title' => ['lt' => 'Naujas žingsnis', 'en' => 'New step'],
        'description' => ['lt' => 'Kodėl verta', 'en' => 'Why useful'],
        'per_term' => true,
        'order' => 3,
        'is_active' => true,
    ])->assertRedirect();

    $step = LearningStep::query()->where('is_system', false)->latest('created_at')->firstOrFail();
    expect($step->reviewed_at?->toIso8601String())->toBe(now()->toIso8601String());

    $step->update(['reviewed_at' => now()->subDays(10)]);
    $this->travel(5)->minutes();

    asUser($curator)->put(route('learningSteps.update', $step), [
        'kind' => LearningStepKind::Action->value,
        'audiences' => [['type' => 'everyone', 'stage' => LearningStage::FirstMonths->value, 'importance' => LearningImportance::Recommended->value]],
        'title' => ['lt' => 'Atnaujintas žingsnis', 'en' => 'Updated step'],
        'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
        'per_term' => false,
        'order' => 4,
        'is_active' => true,
    ])->assertRedirect();

    expect($step->fresh()->reviewed_at?->toIso8601String())->toBe(now()->toIso8601String());
});

test('document search returns hidden documents only to their manager and published ones to every curator, capped at 20', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create();

    $hiddenDoc = Document::factory()->for($institution)->inactive()->create([
        'title' => 'Paslėptas senato reglamentas',
        'status' => DocumentStatus::Hidden,
    ]);
    Document::factory()->for($institution)->count(22)->create([
        'title' => 'Bendras senato dokumentas',
        'status' => DocumentStatus::Published,
    ]);

    $manager = makeUser($tenant);
    $manager->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $manager->givePermissionTo(Permission::findOrCreate('documents.update.*', 'web'));

    $hiddenSearch = asUser($manager)->getJson(route('knowledgeItems.documentSearch', ['q' => 'Paslėptas senato']))->assertOk()->json();
    expect($hiddenSearch)->toHaveCount(1)
        ->and($hiddenSearch[0]['id'])->toBe($hiddenDoc->id)
        ->and($hiddenSearch[0]['status'])->toBe(DocumentStatus::Hidden->value);

    $cappedSearch = asUser($manager)->getJson(route('knowledgeItems.documentSearch', ['q' => 'senato']))->assertOk()->json();
    expect($cappedSearch)->toHaveCount(20);

    $curatorWithoutDocs = makeUser($tenant);
    $curatorWithoutDocs->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));

    $curatorSearch = asUser($curatorWithoutDocs)->getJson(route('knowledgeItems.documentSearch', ['q' => 'senato']))->assertOk()->json();
    expect($curatorSearch)->toHaveCount(20)
        ->and(collect($curatorSearch)->pluck('id'))->not->toContain($hiddenDoc->id);
});

test('glossary sync authorizes update on the step and rejects foreign ids', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $step = KnowledgeItem::factory()->audience(['type' => LearningAudienceType::Purpose, 'source_id' => ResponsibilityPurpose::StudentRepresentation->value])->create();
    $termA = GlossaryTerm::factory()->create();
    $termB = GlossaryTerm::factory()->create();

    $member = makeUser($tenant);
    asUser($member)->put(route('knowledgeItems.glossary.sync', $step), [
        'term_ids' => [$termA->id],
    ])->assertForbidden();

    $curator = makeUser($tenant);
    $curator->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));

    asUser($curator)->put(route('knowledgeItems.glossary.sync', $step), [
        'term_ids' => ['01J00000000000000000000000'],
    ])->assertSessionHasErrors('term_ids.0');

    asUser($curator)->put(route('knowledgeItems.glossary.sync', $step), [
        'term_ids' => [$termA->id, $termB->id],
    ])->assertRedirect();

    expect($step->glossaryTerms()->pluck('glossary_terms.id')->all())->toEqualCanonicalizing([$termA->id, $termB->id]);
});

test('glossary payload includes stepsCount and full translations for editing', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = makeUser($tenant);
    $term = GlossaryTerm::factory()->create([
        'term' => ['lt' => 'Senatas', 'en' => 'Senate'],
        'definition' => ['lt' => 'Aukščiausias savivaldos organas.', 'en' => 'Highest academic body.'],
    ]);
    $steps = KnowledgeItem::factory()->count(2)->create();
    $term->knowledgeItems()->sync($steps->pluck('id'));

    asUser($user)->get(route('glossaryTerms.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/IndexGlossaryTerm')
        ->has('terms', 1)
        ->where('terms.0.id', $term->id)
        ->where('terms.0.itemsCount', 2)
        ->has('terms.0.items', 2)
        ->whereType('terms.0.items.0.id', 'string')
        ->whereType('terms.0.items.0.title', 'string')
        ->where('terms.0.translations.term.lt', 'Senatas')
        ->where('terms.0.translations.term.en', 'Senate'));
});

test('the step page offers the glossary editor only to curators who may update the step', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $step = KnowledgeItem::factory()->audience(['type' => LearningAudienceType::Purpose, 'source_id' => ResponsibilityPurpose::StudentRepresentation->value])->create();
    GlossaryTerm::factory()->create(['term' => ['lt' => 'Kvorumas', 'en' => 'Quorum']]);
    $member = makeUser($tenant);
    $curator = makeUser($tenant);
    $curator->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));

    asUser($member)->get(route('knowledgeItems.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('canUpdate', false)->missing('glossaryOptions'));
    asUser($curator)->get(route('knowledgeItems.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('canUpdate', true)->has('glossaryOptions', 1)->where('glossaryOptions.0.term', 'Kvorumas'));
});

test('a padalinys curator can manage the library with create or update permission', function (string $ability): void {
    $curator = makeUser(Tenant::query()->firstOrFail());
    $curator->givePermissionTo(Permission::findOrCreate("learningSteps.{$ability}.padalinys", 'web'));

    asUser($curator)->get(route('learningSteps.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/IndexLearningStep')
        ->missing('learningNavigation')
        ->has('steps'));
})->with(['create', 'update']);

test('glossary links always open knowledge records regardless of source visibility', function (): void {
    $viewer = makeUser(Tenant::query()->firstOrFail());
    $term = GlossaryTerm::factory()->create();
    $published = Document::factory()->create(['status' => DocumentStatus::Published, 'anonymous_url' => 'https://example.test/guide']);
    $hidden = Document::factory()->create(['status' => DocumentStatus::Hidden, 'anonymous_url' => null]);
    $readableStep = KnowledgeItem::factory()->create([]);
    $hiddenStep = KnowledgeItem::factory()->create([]);
    $unsourced = KnowledgeItem::factory()->create();
    $readableStep->sources()->create(['locale' => 'lt', 'document_id' => $published->id]);
    $hiddenStep->sources()->create(['locale' => 'lt', 'document_id' => $hidden->id]);
    $term->knowledgeItems()->attach([$readableStep->id, $hiddenStep->id, $unsourced->id]);

    $response = asUser($viewer)->get(route('glossaryTerms.index'))->assertOk();
    $steps = collect($response->viewData('page')['props']['terms'][0]['items'])->keyBy('id');

    expect($steps)->toHaveCount(3);
    expect($steps[$readableStep->id])->toBe([
        'id' => $readableStep->id, 'title' => $readableStep->getTranslation('title', app()->getLocale()),
        'url' => route('knowledgeItems.show', $readableStep),
    ]);
    expect($steps[$hiddenStep->id]['url'])->toBe(route('knowledgeItems.show', $hiddenStep));
    expect($steps[$unsourced->id]['url'])->toBe(route('knowledgeItems.show', $unsourced));
});
