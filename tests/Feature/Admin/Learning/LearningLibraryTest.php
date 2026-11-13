<?php

use App\Enums\DocumentStatus;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeItemSource;
use App\Models\LearningStep;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

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

    $withoutSource = KnowledgeItem::factory()->create(['description' => ['lt' => '', 'en' => '']]);

    $response = asUser($curator)->get(route('knowledgeItems.index'))->assertOk();
    $steps = collect($response->viewData('page')['props']['items'])->keyBy('id');

    expect($steps[$expiredStep->id]['canUpdate'])->toBeTrue()
        ->and($steps[$expiredStep->id]['flags'])->toContain('expired_document')
        ->and($steps[$updatedStep->id]['flags'])->toContain('updated_document')
        ->and($steps[$withoutSource->id]['flags'])->toBe([])
        ->and($steps[$withoutSource->id]['hasSource'])->toBeFalse();

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

        'order' => 3,
        'is_active' => true,
    ])->assertRedirect();

    $step = LearningStep::query()->where('title->lt', 'Naujas žingsnis')->firstOrFail();
    expect($step->reviewed_at?->toIso8601String())->toBe(now()->toIso8601String());

    $step->update(['reviewed_at' => now()->subDays(10)]);
    $this->travel(5)->minutes();

    asUser($curator)->put(route('learningSteps.update', $step), [
        'kind' => LearningStepKind::Action->value,
        'audiences' => [['type' => 'everyone', 'stage' => LearningStage::FirstMonths->value, 'importance' => LearningImportance::Recommended->value]],
        'title' => ['lt' => 'Atnaujintas žingsnis', 'en' => 'Updated step'],
        'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],

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

test('the article page offers subject options only to curators who may update it', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $step = KnowledgeItem::factory()->audience(['type' => LearningAudienceType::Purpose, 'source_id' => ResponsibilityPurpose::StudentRepresentation->value])->create();
    InstitutionType::factory()->create(['title' => ['lt' => 'Kvorumo tipas', 'en' => 'Quorum type']]);
    $member = makeUser($tenant);
    $curator = makeUser($tenant);
    $curator->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));

    asUser($member)->get(route('knowledgeItems.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('canUpdate', false)->missing('subjectOptions'));
    asUser($curator)->get(route('knowledgeItems.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('canUpdate', true)->has('subjectOptions.institutionTypes')->has('subjectOptions.dutyTypes'));
});

test('a padalinys curator can manage the library with create or update permission', function (string $ability): void {
    $curator = makeUser(Tenant::query()->firstOrFail());
    $curator->givePermissionTo(Permission::findOrCreate("learningSteps.{$ability}.padalinys", 'web'));

    asUser($curator)->get(route('learningSteps.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/IndexLearningStep')
        ->missing('learningNavigation')
        ->has('steps'));
})->with(['create', 'update']);

test('update-only curators can search documents', function (string $resource): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate("{$resource}.update.*", 'web'));
    asUser($user)->getJson(route('knowledgeItems.documentSearch', ['q' => 'guide']))->assertOk();
})->with(['knowledgeItems', 'learningSteps']);
