<?php

use App\Enums\ResponsibilityPurpose;
use App\Enums\SystemDutyType;
use App\Models\DutyType;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\SystemDutyTypes;
use App\Settings\DutyTypeSettings;
use Database\Factories\RoleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeAdminUser($this->tenant);
});

function purposesPayload(array $purposes = [], array $dutyTypes = []): array
{
    return [
        'purposes' => [
            ...array_fill_keys(array_map(fn (ResponsibilityPurpose $purpose) => $purpose->value, ResponsibilityPurpose::cases()), null),
            ...$purposes,
        ],
        'duty_types' => [
            ...array_fill_keys(array_map(fn (SystemDutyType $part) => $part->value, SystemDutyType::cases()), null),
            ...$dutyTypes,
        ],
    ];
}

test('managing settings is the settings permission, not a role id', function (): void {
    $member = makeUser($this->tenant);

    asUser($member)->get(route('settings.purposes.edit'))->assertForbidden();

    $settingsManager = Role::factory()->create();
    $settingsManager->givePermissionTo('settings.update.*');
    $member->duties()->first()->assignRole($settingsManager);

    asUser($member->fresh())->get(route('settings.purposes.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/EditSystemPurposesSettings')
            ->has('purposes', count(ResponsibilityPurpose::cases()))
            ->has('dutyTypes', count(SystemDutyType::cases())));
});

test('a purpose moves to the chosen responsibility and leaves the old one', function (): void {
    $old = RoleFactory::forPurpose(ResponsibilityPurpose::MemberRegistration);
    $new = Role::factory()->create();

    asUser($this->admin)
        ->post(route('settings.purposes.update'), purposesPayload([
            'member_registration' => $new->id,
            'student_representation' => Role::forPurpose(ResponsibilityPurpose::StudentRepresentation)?->id,
        ]))
        ->assertSessionHasNoErrors();

    expect($new->fresh()->purpose)->toBe(ResponsibilityPurpose::MemberRegistration)
        ->and($old->fresh()->purpose)->toBeNull();
});

test('one responsibility cannot carry two purposes', function (): void {
    $role = Role::factory()->create();

    asUser($this->admin)
        ->post(route('settings.purposes.update'), purposesPayload(['member_registration' => $role->id, 'student_rep_coordination' => $role->id]))
        ->assertSessionHasErrors();
});

test('the chosen contacts type wins over the old slug and cannot be deleted', function (): void {
    $type = DutyType::factory()->create(['slug' => 'musu-koordinatoriai']);

    asUser($this->admin)
        ->post(route('settings.purposes.update'), purposesPayload(
            ['student_representation' => Role::forPurpose(ResponsibilityPurpose::StudentRepresentation)?->id],
            ['coordinator' => $type->id],
        ))
        ->assertSessionHasNoErrors();

    expect(app(DutyTypeSettings::class)->coordinator_type_id)->toBe($type->id)
        ->and(app(SystemDutyTypes::class)->id(SystemDutyType::Coordinator))->toBe($type->id);

    asUser($this->admin)->delete(route('dutyTypes.destroy', $type))->assertSessionHas('error');

    $this->assertNotSoftDeleted($type);
});

test('curators and mentors need different types', function (): void {
    $type = DutyType::factory()->create();

    asUser($this->admin)
        ->post(route('settings.purposes.update'), purposesPayload([], ['curator' => $type->id, 'mentor' => $type->id]))
        ->assertSessionHasErrors('duty_types.mentor');
});
