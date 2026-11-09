<?php

use App\Enums\DocumentStatus;
use App\Enums\LearningImportance;
use App\Enums\LearningLayer;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Models\Document;
use App\Models\DutyType;
use App\Models\GlossaryTerm;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\LearningStep;
use App\Models\LearningStepSource;
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
    $expiredStep = LearningStep::factory()->create([
        'kind' => LearningStepKind::Knowledge,
        'reviewed_at' => now(),
    ]);
    LearningStepSource::factory()->for($expiredStep, 'step')->create([
        'locale' => 'lt',
        'document_id' => $expiredDoc->id,
        'url' => null,
    ]);

    $updatedDoc = Document::factory()->create([
        'effective_date' => null,
        'expiration_date' => null,
        'sharepoint_modified_at' => now()->subHour(),
    ]);
    $updatedStep = LearningStep::factory()->create([
        'kind' => LearningStepKind::Knowledge,
        'reviewed_at' => now()->subDay(),
    ]);
    LearningStepSource::factory()->for($updatedStep, 'step')->create([
        'locale' => 'lt',
        'document_id' => $updatedDoc->id,
        'url' => null,
    ]);

    $missingSourceStep = LearningStep::factory()->create([
        'kind' => LearningStepKind::Knowledge,
    ]);

    $response = asUser($curator)->get(route('learningSteps.index'))->assertOk();
    $steps = collect($response->viewData('page')['props']['steps'])->keyBy('id');

    expect($steps[$expiredStep->id]['canUpdate'])->toBeTrue()
        ->and($steps[$expiredStep->id]['flags'])->toContain('expired_document')
        ->and($steps[$updatedStep->id]['flags'])->toContain('updated_document')
        ->and($steps[$missingSourceStep->id]['flags'])->toContain('missing_source');

    $member = makeUser($tenant);
    $memberSteps = collect(asUser($member)->get(route('learningSteps.index'))->assertOk()->viewData('page')['props']['steps'])->keyBy('id');

    expect($memberSteps[$expiredStep->id]['canUpdate'])->toBeFalse()
        ->and($memberSteps[$expiredStep->id]['flags'])->toBe([]);
});

test('create and edit pages pass only tenant layer and managed tenants to a padalinys curator, and all options to a global curator', function (): void {
    $ownTenant = Tenant::query()->firstOrFail();
    $otherTenant = Tenant::factory()->create();
    InstitutionType::factory()->create();
    DutyType::factory()->create();

    $padalinysCurator = makeUser($ownTenant);
    $padalinysCurator->givePermissionTo(Permission::findOrCreate('learningSteps.create.padalinys', 'web'));
    $padalinysCurator->givePermissionTo(Permission::findOrCreate('learningSteps.update.padalinys', 'web'));

    $tenantStep = LearningStep::factory()->create([
        'layer' => LearningLayer::Tenant,
        'tenant_id' => $ownTenant->id,
        'stage' => LearningStage::BeforeStart,
    ]);

    asUser($padalinysCurator)->get(route('learningSteps.create'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/CreateLearningStep')
        ->where('layers', [LearningLayer::Tenant->value])
        ->has('tenants', 1)
        ->where('tenants.0.id', $ownTenant->id)
        ->where('institutionTypes', [])
        ->where('dutyTypes', []));

    asUser($padalinysCurator)->get(route('learningSteps.edit', $tenantStep))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/EditLearningStep')
        ->where('layers', [LearningLayer::Tenant->value])
        ->has('tenants', 1)
        ->where('tenants.0.id', $ownTenant->id));

    $globalCurator = makeUser($ownTenant);
    $globalCurator->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $globalCurator->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));

    $createProps = asUser($globalCurator)->get(route('learningSteps.create'))->assertOk()->viewData('page')['props'];
    expect($createProps['layers'])->toBe(array_map(fn (LearningLayer $layer): string => $layer->value, LearningLayer::cases()))
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
        'layer' => LearningLayer::Foundation->value,
        'stage' => LearningStage::BeforeStart->value,
        'importance' => LearningImportance::Essential->value,
        'title' => ['lt' => 'Naujas žingsnis', 'en' => 'New step'],
        'description' => ['lt' => 'Kodėl verta', 'en' => 'Why useful'],
        'per_term' => true,
        'order' => 3,
        'is_active' => true,
    ])->assertRedirect();

    $step = LearningStep::query()->latest('created_at')->firstOrFail();
    expect($step->reviewed_at?->toIso8601String())->toBe(now()->toIso8601String());

    $step->update(['reviewed_at' => now()->subDays(10)]);
    $this->travel(5)->minutes();

    asUser($curator)->put(route('learningSteps.update', $step), [
        'kind' => LearningStepKind::Action->value,
        'layer' => LearningLayer::Foundation->value,
        'stage' => LearningStage::FirstMonths->value,
        'importance' => LearningImportance::Recommended->value,
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

    $hiddenSearch = asUser($manager)->getJson(route('learningSteps.documentSearch', ['q' => 'Paslėptas senato']))->assertOk()->json();
    expect($hiddenSearch)->toHaveCount(1)
        ->and($hiddenSearch[0]['id'])->toBe($hiddenDoc->id)
        ->and($hiddenSearch[0]['status'])->toBe(DocumentStatus::Hidden->value);

    $cappedSearch = asUser($manager)->getJson(route('learningSteps.documentSearch', ['q' => 'senato']))->assertOk()->json();
    expect($cappedSearch)->toHaveCount(20);

    $curatorWithoutDocs = makeUser($tenant);
    $curatorWithoutDocs->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));

    $curatorSearch = asUser($curatorWithoutDocs)->getJson(route('learningSteps.documentSearch', ['q' => 'senato']))->assertOk()->json();
    expect($curatorSearch)->toHaveCount(20)
        ->and(collect($curatorSearch)->pluck('id'))->not->toContain($hiddenDoc->id);
});

test('glossary sync authorizes update on the step and rejects foreign ids', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $step = LearningStep::factory()->create(['layer' => LearningLayer::Foundation]);
    $termA = GlossaryTerm::factory()->create();
    $termB = GlossaryTerm::factory()->create();

    $member = makeUser($tenant);
    asUser($member)->put(route('learningSteps.glossary.sync', $step), [
        'term_ids' => [$termA->id],
    ])->assertForbidden();

    $curator = makeUser($tenant);
    $curator->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));

    asUser($curator)->put(route('learningSteps.glossary.sync', $step), [
        'term_ids' => ['01J00000000000000000000000'],
    ])->assertSessionHasErrors('term_ids.0');

    asUser($curator)->put(route('learningSteps.glossary.sync', $step), [
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
    $steps = LearningStep::factory()->count(2)->create();
    $term->steps()->sync($steps->pluck('id'));

    asUser($user)->get(route('glossaryTerms.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/IndexGlossaryTerm')
        ->has('terms', 1)
        ->where('terms.0.id', $term->id)
        ->where('terms.0.stepsCount', 2)
        ->where('terms.0.translations.term.lt', 'Senatas')
        ->where('terms.0.translations.term.en', 'Senate'));
});

test('the step page offers the glossary editor only to curators who may update the step', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $step = LearningStep::factory()->create(['layer' => LearningLayer::Foundation]);
    GlossaryTerm::factory()->create(['term' => ['lt' => 'Kvorumas', 'en' => 'Quorum']]);
    $member = makeUser($tenant);
    $curator = makeUser($tenant);
    $curator->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));

    asUser($member)->get(route('learningSteps.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('canUpdate', false)->where('glossaryOptions', []));
    asUser($curator)->get(route('learningSteps.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('canUpdate', true)->has('glossaryOptions', 1)->where('glossaryOptions.0.term', 'Kvorumas'));
});
