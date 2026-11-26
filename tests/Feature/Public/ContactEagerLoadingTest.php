<?php

use App\Actions\GenerateInstitutionAlias;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\Tenant;
use App\Services\SystemDutyTypes;
use App\Settings\DutyTypeSettings;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $this->institution = Institution::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->type = DutyType::factory()->create([
        'title' => ['lt' => 'Test duty type', 'en' => 'Test duty type'],
    ]);

    $this->duty = Duty::factory()->create([
        'institution_id' => $this->institution->id,
        'contacts_grouping' => 'none',
    ]);

    // Attach the type to the duty
    $this->duty->types()->attach($this->type->id);

    // Attach a user to the duty
    $this->user = makeUser($this->tenant);
    $this->duty->users()->attach($this->user->id, ['start_date' => now()->subDay()]);
});

test('duty type contacts show current holders of matching duties once', function (): void {
    $tenant = Tenant::main();
    $alias = GenerateInstitutionAlias::CENTRAL_OFFICE_ALIAS;
    $institution = Institution::where('alias', $alias)->first()
        ?? Institution::factory()->for($tenant)->create(['alias' => $alias, 'is_active' => true]);

    $settings = app(DutyTypeSettings::class);
    $settings->coordinator_type_id = $this->type->id;
    $settings->save();
    app(SystemDutyTypes::class)->flush();

    $childType = DutyType::factory()->create(['parent_id' => $this->type->id]);
    $rootDuty = Duty::factory()->for($institution)->create(['order' => 1]);
    $childDuty = Duty::factory()->for($institution)->create(['order' => 2]);
    $unrelatedDuty = Duty::factory()->for($institution)->create();
    $rootDuty->types()->attach($this->type);
    $childDuty->types()->attach($childType);

    $holder = makeUser($tenant);
    $rootDuty->users()->attach($holder->id, ['start_date' => today()->subDay()]);
    $childDuty->users()->attach($holder->id, ['start_date' => today()]);
    $unrelatedDuty->users()->attach($holder->id, ['start_date' => today()]);
    $rootDuty->users()->attach(makeUser($tenant)->id, ['start_date' => today()->addDay()]);
    $childDuty->users()->attach(makeUser($tenant)->id, ['start_date' => today()->subDays(2), 'end_date' => today()->subDay()]);

    $this->get(LocalizedRouteSlugs::route('contacts.dutyType', ['subdomain' => 'www', 'type' => 'koordinatoriai'], 'lt'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('contacts', 1)
            ->where('contacts.0.id', $holder->id)
            ->has('contacts.0.duties', 2)
            ->where('contacts.0.duties.0.id', $rootDuty->id)
            ->where('contacts.0.duties.1.id', $childDuty->id));
});

test('public contacts preserve cross-locale pronouns and the assignment name override', function (string $grouping, string $contactPath): void {
    $this->user->update(['name' => 'Jonas Jonaitis', 'pronouns' => ['lt' => '', 'en' => 'she / her']]);
    $this->duty->update(['name' => ['lt' => 'Koordinatorius', 'en' => 'Coordinator'], 'contacts_grouping' => $grouping]);
    $this->duty->users()->updateExistingPivot($this->user->id, ['use_original_duty_name' => true]);

    $this->get(route('contacts.institution', [
        'subdomain' => 'www', 'lang' => 'lt', 'contactsString' => 'kontaktai', 'institution' => $this->institution->id,
    ]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Public/Contacts/ShowInstitution')
        ->where($contactPath.'.name', 'Jonas Jonaitis')
        ->where($contactPath.'.duty_pronouns.en', 'she / her')
        ->where($contactPath.'.duties.0.pivot.use_original_duty_name', true)
    );
})->with([
    'ungrouped' => ['none', 'contacts.0'],
    'ordered by tenant' => ['tenant', 'contacts.0'],
    'ordered by study program' => ['study_program', 'contacts.0'],
]);
