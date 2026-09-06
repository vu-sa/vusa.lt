<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\PublicInstitution;
use App\Models\Type;
use App\Models\User;
use App\Services\PublicInstitutionSearchIndexBuilder;
use App\Settings\AtstovavimasSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    AtstovavimasSettings::clearStudentRepTypeCache();
});

test('PublicInstitutionSearchIndexBuilder includes contacts, current user names, and representation flag', function (): void {
    $rootType = Type::query()->firstOrCreate(
        ['slug' => 'studentu-atstovu-organas'],
        ['title' => json_encode(['lt' => 'Studentų atstovų organas', 'en' => 'Student representative body'])],
    );

    $childType = Type::factory()->create([
        'title' => 'Fakulteto taryba',
        'slug' => 'fakulteto-taryba',
        'parent_id' => $rootType->id,
    ]);

    $institution = Institution::factory()->create([
        'is_active' => true,
    ]);
    $institution->types()->attach($childType);

    $duty = Duty::factory()->create([
        'institution_id' => $institution->id,
        'name' => 'Atstovas',
    ]);

    $user = User::factory()->create([
        'name' => 'Jonas Jonaitis',
        'profile_photo_path' => 'photos/jonas.jpg',
        'profile_photo_focal_point' => '50% 25%',
    ]);

    Dutiable::factory()
        ->forUser($user)
        ->forDuty($duty)
        ->create([
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

    $publicInstitution = PublicInstitution::findOrFail($institution->id);
    $builder = app(PublicInstitutionSearchIndexBuilder::class);
    $searchable = $builder->build($publicInstitution);

    expect($searchable['is_student_representation'])->toBeTrue()
        ->and($searchable['current_user_names'])->toContain('Jonas Jonaitis')
        ->and($searchable['contacts'])->toHaveCount(1)
        ->and($searchable['contacts'][0]['name'])->toBe('Jonas Jonaitis')
        ->and($searchable['contacts'][0]['profile_photo_path'])->toBe('photos/jonas.jpg')
        ->and($searchable['contacts'][0]['profile_photo_focal_point'])->toBe('50% 25%')
        ->and($searchable['has_contacts'])->toBeTrue();
});

test('PublicInstitutionSearchIndexBuilder sets is_student_representation false for non-student-rep types', function (): void {
    $otherType = Type::factory()->create([
        'title' => 'Padalinys',
        'slug' => 'padalinys',
    ]);

    $institution = Institution::factory()->create([
        'is_active' => true,
    ]);
    $institution->types()->attach($otherType);

    $publicInstitution = PublicInstitution::findOrFail($institution->id);
    $builder = app(PublicInstitutionSearchIndexBuilder::class);
    $searchable = $builder->build($publicInstitution);

    expect($searchable['is_student_representation'])->toBeFalse()
        ->and($searchable['contacts'])->toBeEmpty()
        ->and($searchable['has_contacts'])->toBeFalse();
});

test('AtstovavimasSettings can configure custom student rep root type', function (): void {
    $settings = app(AtstovavimasSettings::class);

    $customType = Type::factory()->create([
        'title' => 'Mano atstovai',
        'slug' => 'mano-atstovai',
    ]);

    $childType = Type::factory()->create([
        'title' => 'Komitetas',
        'slug' => 'komitetas',
        'parent_id' => $customType->id,
    ]);

    $settings->student_rep_root_type_id = $customType->id;
    $settings->save();
    AtstovavimasSettings::clearStudentRepTypeCache();

    expect($settings->getStudentRepInstitutionTypeIds()->toArray())->toContain($customType->id, $childType->id)
        ->and($settings->getStudentRepInstitutionTypeSlugs()->toArray())->toContain('mano-atstovai', 'komitetas');
});
