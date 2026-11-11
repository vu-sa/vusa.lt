<?php

use App\Enums\ResponsibilityPurpose;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\GlossaryTerm;
use App\Models\Institution;
use App\Models\InstitutionSecretary;
use App\Models\KnowledgeItem;
use App\Models\LearningStep;
use App\Models\Permission;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\TypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('members can open learning pages with their typed payloads', function (string $routeName, string $component, string $prop): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    asUser($user)->get(route($routeName))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component($component)->has($prop)->missing('canSeeReps')->missing('learningNavigation'));
})->with([
    'path' => ['learningPath.show', 'Admin/Learning/ShowLearningPath', 'path.stages'],
    'documents' => ['knowledgeBase.show', 'Admin/Knowledge/ShowKnowledgeBase', 'knowledge'],
    'glossary' => ['glossaryTerms.index', 'Admin/Knowledge/IndexGlossaryTerm', 'terms'],
]);

test('a member can open a library step and its presenter contract', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->create();
    asUser($user)->get(route('learningSteps.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/ShowLearningStep')->where('step.id', $step->id)
        ->whereType('step.title', 'string')->missing('step.insightsCount')->missing('step.source'));
});

test('members without step rights receive a direct 403 for the step editor', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    asUser($user)->get(route('learningSteps.create'))->assertForbidden();
});

test('global curators can open create and edit stubs', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::factory()->create();
    asUser($user)->get(route('learningSteps.create'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Learning/CreateLearningStep'));
    asUser($user)->get(route('learningSteps.edit', $step))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Learning/EditLearningStep')->has('step.title.lt'));
});

test('a step record exposes localized glossary terms and the members own progress eligibility', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = KnowledgeItem::factory()->create(['reviewed_at' => '2026-11-01']);
    $term = GlossaryTerm::factory()->create([
        'term' => ['lt' => 'Kvorumas', 'en' => 'Quorum'],
        'definition' => ['lt' => 'Būtinas narių skaičius.', 'en' => 'The required number of members.'],
    ]);
    $step->glossaryTerms()->attach($term);

    asUser($user)->get(route('knowledgeItems.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('inPath', true)
        ->where('item.reviewedAt', '2026-11-01')
        ->has('item.glossaryTerms', 1)
        ->where('item.glossaryTerms.0.id', $term->id)
        ->where('item.glossaryTerms.0.term', 'Kvorumas')
        ->where('item.glossaryTerms.0.definition', 'Būtinas narių skaičius.')
        ->whereType('item.glossaryTerms.0.id', 'string')
        ->whereType('item.glossaryTerms.0.term', 'string')
        ->whereType('item.glossaryTerms.0.definition', 'string')
        ->has('coordinators')->has('institutionContacts'));
});

test('institution contacts contain only a representatives own institutions and current term secretaries', function (): void {
    $this->freezeTime();
    $tenant = Tenant::query()->firstOrFail();
    $rep = makeUser($tenant);
    $institution = Institution::factory()->for($tenant)->create();
    $duty = Duty::factory()->for($institution)->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    Dutiable::factory()->forUser($rep)->create(['duty_id' => $duty->id, 'start_date' => today()->subMonth(), 'end_date' => null]);

    $chair = User::factory()->create();
    $chairDuty = Duty::factory()->for($institution)->create(['email' => 'chair@example.test']);
    $chairDuty->types()->attach(TypeSeeder::CHAIR_DUTY_TYPE_ID);
    Dutiable::factory()->forUser($chair)->create(['duty_id' => $chairDuty->id, 'start_date' => today()->subMonth(), 'end_date' => null]);

    $secretary = User::factory()->create();
    $cadence = Cadence::factory()->create(['institution_id' => $institution->id, 'start_date' => today()->subMonth(), 'end_date' => today()->addYear()]);
    InstitutionSecretary::create(['institution_id' => $institution->id, 'cadence_id' => $cadence->id, 'user_id' => $secretary->id]);
    $oldCadence = Cadence::factory()->create(['institution_id' => $institution->id, 'start_date' => today()->subYears(2), 'end_date' => today()->subYear()]);
    InstitutionSecretary::create(['institution_id' => $institution->id, 'cadence_id' => $oldCadence->id, 'user_id' => User::factory()->create()->id]);

    $otherInstitution = Institution::factory()->for($tenant)->create();
    $otherDuty = Duty::factory()->for($otherInstitution)->create();
    $otherDuty->types()->attach(TypeSeeder::CHAIR_DUTY_TYPE_ID);
    Dutiable::factory()->forUser(User::factory()->create())->create(['duty_id' => $otherDuty->id, 'start_date' => today()->subMonth(), 'end_date' => null]);

    asUser($rep)->get(route('learningPath.show'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('institutionContacts', 1)
        ->where('institutionContacts.0.id', $institution->id)
        ->whereType('institutionContacts.0.title', 'string')
        ->has('institutionContacts.0.contacts', 2)
        ->where('institutionContacts.0.contacts.0.name', $chair->name)
        ->where('institutionContacts.0.contacts.0.email', 'chair@example.test')
        ->whereType('institutionContacts.0.contacts.0.role', 'string')
        ->where('institutionContacts.0.contacts.1.name', $secretary->name)
        ->where('institutionContacts.0.contacts.1.email', $secretary->email)
        ->whereType('institutionContacts.0.contacts.1.role', 'string'));
});

test('members without representation duties receive no institution contacts', function (): void {
    asUser(makeUser(Tenant::query()->firstOrFail()))->get(route('learningPath.show'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->has('institutionContacts', 0));
});

test('library records outside a members path do not offer personal progress', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->forTenant(Tenant::factory()->create())->create();
    asUser($user)->get(route('learningSteps.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('inPath', false)->missing('step.glossaryTerms')->where('step.reviewedAt', null));
});
