<?php

use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Pivots\Dutiable;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
    
    $this->user = makeUser($this->tenant);
    $this->admin = makeStudyProgramAdmin($this->tenant);
    
    $this->studyProgram = StudyProgram::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => ['lt' => 'Informatikos bakalauras', 'en' => 'Computer Science Bachelor'],
        'degree' => 'BA',
    ]);
});

function makeStudyProgramAdmin($tenant): User
{
    $user = makeUser($tenant);
    $user->duties()->first()->assignRole('Communication Coordinator');
    return $user;
}

describe('auth: simple user without permissions', function () {
    beforeEach(function () {
        asUser($this->user)->get(route('dashboard'))->assertStatus(200);
    });

    test('cannot index study programs', function () {
        asUser($this->user)
            ->get(route('studyPrograms.index'))
            ->assertStatus(302)
            ->assertRedirectToRoute('dashboard');
    });

    test('cannot access study program create page', function () {
        asUser($this->user)
            ->get(route('studyPrograms.create'))
            ->assertStatus(302);
    });

    test('cannot store study program', function () {
        $validData = [
            'name' => ['lt' => 'Test StudyProgram', 'en' => 'Test StudyProgram EN'],
            'degree' => 'MA',
            'tenant_id' => $this->tenant->id,
        ];

        asUser($this->user)
            ->post(route('studyPrograms.store'), $validData)
            ->assertStatus(302)
            ->assertRedirectToRoute('dashboard');

        $this->assertDatabaseMissing('study_programs', [
            'name->lt' => 'Test StudyProgram',
        ]);
    });

    test('cannot access study program edit page', function () {
        asUser($this->user)
            ->get(route('studyPrograms.edit', $this->studyProgram))
            ->assertStatus(302);
    });

    test('cannot update study program', function () {
        $updateData = [
            'name' => ['lt' => 'Updated StudyProgram', 'en' => 'Updated StudyProgram EN'],
            'degree' => 'PhD',
            'tenant_id' => $this->tenant->id,
        ];

        asUser($this->user)
            ->patch(route('studyPrograms.update', $this->studyProgram), $updateData)
            ->assertStatus(302)
            ->assertRedirectToRoute('dashboard');

        $this->assertDatabaseMissing('study_programs', [
            'name->lt' => 'Updated StudyProgram',
        ]);
    });

    test('cannot delete study program', function () {
        asUser($this->user)
            ->delete(route('studyPrograms.destroy', $this->studyProgram))
            ->assertStatus(302);

        $this->assertDatabaseHas('study_programs', [
            'id' => $this->studyProgram->id,
        ]);
    });

    test('cannot access merge study programs page', function () {
        asUser($this->user)
            ->get(route('studyPrograms.merge'))
            ->assertStatus(302);
    });

    test('cannot merge study programs', function () {
        $otherStudyProgram = StudyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Kita programa'],
            'degree' => 'MA',
        ]);

        $mergeData = [
            'target_study_program_id' => $this->studyProgram->id,
            'source_study_program_ids' => [$otherStudyProgram->id],
        ];

        asUser($this->user)
            ->post(route('studyPrograms.mergeStudyPrograms'), $mergeData)
            ->assertStatus(302);

        // Both study programs should still exist
        $this->assertDatabaseHas('study_programs', ['id' => $this->studyProgram->id]);
        $this->assertDatabaseHas('study_programs', ['id' => $otherStudyProgram->id]);
    });
});

describe('auth: admin with permissions', function () {
    beforeEach(function () {
        asUser($this->admin)->get(route('dashboard'))->assertStatus(200);
    });

    test('can index study programs', function () {
        asUser($this->admin)
            ->get(route('studyPrograms.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/IndexStudyProgram')
                ->has('studyPrograms')
                ->has('studyPrograms.data')
                ->has('studyPrograms.meta')
                ->has('filters')
                ->has('sorting')
            );
    });

    test('can access study program create page', function () {
        asUser($this->admin)
            ->get(route('studyPrograms.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/CreateStudyProgram')
                ->has('tenants')
            );
    });

    test('can store study program with valid data', function () {
        $uniqueSuffix = time();
        $validData = [
            'name' => ['lt' => "Teisės bakalauras {$uniqueSuffix}", 'en' => "Law Bachelor {$uniqueSuffix}"],
            'degree' => 'BA',
            'tenant_id' => $this->tenant->id,
        ];

        asUser($this->admin)
            ->post(route('studyPrograms.store'), $validData)
            ->assertStatus(302)
            ->assertRedirect(route('studyPrograms.index'))
            ->assertSessionHas('success', 'Study program created successfully.');

        $this->assertDatabaseHas('study_programs', [
            'name->lt' => "Teisės bakalauras {$uniqueSuffix}",
            'name->en' => "Law Bachelor {$uniqueSuffix}",
            'degree' => 'BA',
            'tenant_id' => $this->tenant->id,
        ]);
    });

    test('cannot store study program with invalid data', function () {
        $invalidData = [
            'name' => ['lt' => ''], // Required field empty
            'degree' => '',
            'tenant_id' => 999999, // Non-existent tenant
        ];

        asUser($this->admin)
            ->post(route('studyPrograms.store'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['name.lt', 'degree', 'tenant_id']);

        $this->assertDatabaseMissing('study_programs', [
            'degree' => '',
        ]);
    });

    test('can access study program edit page', function () {
        asUser($this->admin)
            ->get(route('studyPrograms.edit', $this->studyProgram))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/EditStudyProgram')
                ->has('studyProgram')
                ->has('tenants')
                ->where('studyProgram.id', $this->studyProgram->id)
            );
    });

    test('can update study program with valid data', function () {
        $updateData = [
            'name' => ['lt' => 'Atnaujinta programa', 'en' => 'Updated Program'],
            'degree' => 'MA',
            'tenant_id' => $this->tenant->id,
        ];

        asUser($this->admin)
            ->patch(route('studyPrograms.update', $this->studyProgram), $updateData)
            ->assertStatus(302)
            ->assertRedirect(route('studyPrograms.index'))
            ->assertSessionHas('success', 'Study program updated successfully.');

        $this->assertDatabaseHas('study_programs', [
            'id' => $this->studyProgram->id,
            'name->lt' => 'Atnaujinta programa',
            'name->en' => 'Updated Program',
            'degree' => 'MA',
        ]);
    });

    test('cannot update study program with invalid data', function () {
        $invalidData = [
            'name' => ['lt' => ''], // Required field empty
            'degree' => '',
            'tenant_id' => 999999, // Non-existent tenant
        ];

        asUser($this->admin)
            ->patch(route('studyPrograms.update', $this->studyProgram), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['name.lt', 'degree', 'tenant_id']);

        // Original data should remain unchanged
        $this->assertDatabaseHas('study_programs', [
            'id' => $this->studyProgram->id,
            'name->lt' => 'Informatikos bakalauras',
        ]);
    });

    test('can delete study program when not in use', function () {
        asUser($this->admin)
            ->delete(route('studyPrograms.destroy', $this->studyProgram))
            ->assertStatus(302)
            ->assertRedirect(route('studyPrograms.index'))
            ->assertSessionHas('success', 'Study program deleted successfully.');

        $this->assertDatabaseMissing('study_programs', [
            'id' => $this->studyProgram->id,
        ]);
    });

    test('cannot delete study program when in use by dutiables', function () {
        // Create a dutiable that uses this study program
        $dutiable = Dutiable::factory()->create([
            'study_program_id' => $this->studyProgram->id,
        ]);

        asUser($this->admin)
            ->delete(route('studyPrograms.destroy', $this->studyProgram))
            ->assertStatus(302)
            ->assertSessionHas('error')
            ->assertSessionHas('error', 'Cannot delete study program. It is currently assigned to 1 duty assignment(s).');

        $this->assertDatabaseHas('study_programs', [
            'id' => $this->studyProgram->id,
        ]);
    });

    test('can access merge study programs page', function () {
        asUser($this->admin)
            ->get(route('studyPrograms.merge'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/MergeStudyPrograms')
                ->has('studyPrograms')
            );
    });

    test('can merge study programs successfully', function () {
        $sourceProgram1 = StudyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Programa 1'],
            'degree' => 'BA',
        ]);

        $sourceProgram2 = StudyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Programa 2'],
            'degree' => 'BA',
        ]);

        // Create dutiables for source programs
        $dutiable1 = Dutiable::factory()->create(['study_program_id' => $sourceProgram1->id]);
        $dutiable2 = Dutiable::factory()->create(['study_program_id' => $sourceProgram2->id]);

        $mergeData = [
            'target_study_program_id' => $this->studyProgram->id,
            'source_study_program_ids' => [$sourceProgram1->id, $sourceProgram2->id],
        ];

        asUser($this->admin)
            ->post(route('studyPrograms.mergeStudyPrograms'), $mergeData)
            ->assertStatus(302)
            ->assertRedirect(route('studyPrograms.index'))
            ->assertSessionHas('success', 'Study programs merged successfully.');

        // Target study program should still exist
        $this->assertDatabaseHas('study_programs', ['id' => $this->studyProgram->id]);

        // Source study programs should be deleted
        $this->assertDatabaseMissing('study_programs', ['id' => $sourceProgram1->id]);
        $this->assertDatabaseMissing('study_programs', ['id' => $sourceProgram2->id]);

        // Dutiables should be transferred to target study program
        $this->assertDatabaseHas('dutiables', [
            'id' => $dutiable1->id,
            'study_program_id' => $this->studyProgram->id,
        ]);
        $this->assertDatabaseHas('dutiables', [
            'id' => $dutiable2->id,
            'study_program_id' => $this->studyProgram->id,
        ]);
    });

    test('cannot merge study programs with invalid data', function () {
        $invalidData = [
            'target_study_program_id' => 999999, // Non-existent study program
            'source_study_program_ids' => [999998, 999997], // Non-existent study programs
        ];

        asUser($this->admin)
            ->post(route('studyPrograms.mergeStudyPrograms'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['target_study_program_id', 'source_study_program_ids.0', 'source_study_program_ids.1']);
    });

    test('cannot include target study program in source list', function () {
        $sourceProgram = StudyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Source programa'],
            'degree' => 'MA',
        ]);

        $invalidData = [
            'target_study_program_id' => $this->studyProgram->id,
            'source_study_program_ids' => [$sourceProgram->id, $this->studyProgram->id], // Target included in sources
        ];

        asUser($this->admin)
            ->post(route('studyPrograms.mergeStudyPrograms'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['source_study_program_ids.1']);
    });
});

describe('model relationships and factories', function () {
    test('study program belongs to tenant', function () {
        expect($this->studyProgram->tenant)->toBeInstanceOf(Tenant::class);
        expect($this->studyProgram->tenant->id)->toBe($this->tenant->id);
    });

    test('study program can have dutiables', function () {
        $dutiable = Dutiable::factory()->create(['study_program_id' => $this->studyProgram->id]);
        
        $this->studyProgram->refresh();
        
        expect($this->studyProgram->dutiables)->toHaveCount(1);
        expect($this->studyProgram->dutiables->first()->id)->toBe($dutiable->id);
    });

    test('study program has translatable name field', function () {
        expect($this->studyProgram->getTranslations('name'))->toBeArray();
        expect($this->studyProgram->getTranslations('name'))->toHaveKey('lt');
        expect($this->studyProgram->getTranslations('name'))->toHaveKey('en');
        expect($this->studyProgram->getTranslation('name', 'lt'))->toBe('Informatikos bakalauras');
        expect($this->studyProgram->getTranslation('name', 'en'))->toBe('Computer Science Bachelor');
    });

    test('study program factory creates valid data', function () {
        $studyProgram = StudyProgram::factory()->create();
        
        expect($studyProgram->getTranslations('name'))->toBeArray();
        expect($studyProgram->degree)->toBeString();
        expect($studyProgram->tenant_id)->toBeInt();
        expect($studyProgram->tenant)->toBeInstanceOf(Tenant::class);
    });
});

describe('api filtering and search', function () {
    beforeEach(function () {
        // Create additional study programs for testing
        StudyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Matematikos magistras', 'en' => 'Mathematics Master'],
            'degree' => 'MA',
        ]);

        StudyProgram::factory()->create([
            'tenant_id' => $this->otherTenant->id,
            'name' => ['lt' => 'Fizikos bakalauras', 'en' => 'Physics Bachelor'],
            'degree' => 'BA',
        ]);
    });

    test('can filter study programs by search term', function () {
        asUser($this->admin)
            ->get(route('studyPrograms.index', ['search' => 'Informatikos']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/IndexStudyProgram')
                ->has('studyPrograms.data')
                ->where('studyPrograms.data', function ($data) {
                    return collect($data)->contains('name', 'Informatikos bakalauras');
                })
            );
    });

    test('can filter study programs by degree', function () {
        // Create a study program with MA degree for this test
        StudyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
            'degree' => 'MA',
        ]);

        asUser($this->admin)
            ->get(route('studyPrograms.index', ['degree' => 'MA']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/IndexStudyProgram')
                ->has('studyPrograms.data')
                ->where('studyPrograms.data.0.degree', 'MA')
            );
    });

    test('pagination works correctly', function () {
        // Create more study programs to test pagination
        StudyProgram::factory()->count(25)->create(['tenant_id' => $this->tenant->id]);

        asUser($this->admin)
            ->get(route('studyPrograms.index', ['per_page' => 10]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/IndexStudyProgram')
                ->has('studyPrograms.data', 10)
                ->where('studyPrograms.meta.per_page', 10)
                ->etc()
            );
    });
});

describe('edge cases and validation', function () {
    test('study program name must be unique within tenant', function () {
        $existingProgram = StudyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Unique Program'],
            'degree' => 'BA',
        ]);

        $duplicateData = [
            'name' => ['lt' => 'Unique Program'], // Same name
            'degree' => 'MA',
            'tenant_id' => $this->tenant->id,
        ];

        asUser($this->admin)
            ->post(route('studyPrograms.store'), $duplicateData)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    });

    test('study program handles special characters in name', function () {
        $uniqueSuffix = time();
        $specialCharsData = [
            'name' => ['lt' => "Programą su šiaudiniais žodžiais {$uniqueSuffix}", 'en' => "Program with special chars & symbols {$uniqueSuffix}"],
            'degree' => 'PHD', // Updated to use enum value
            'tenant_id' => $this->tenant->id,
        ];

        asUser($this->admin)
            ->post(route('studyPrograms.store'), $specialCharsData)
            ->assertStatus(302)
            ->assertRedirect(route('studyPrograms.index'));

        $this->assertDatabaseHas('study_programs', [
            'name->lt' => "Programą su šiaudiniais žodžiais {$uniqueSuffix}",
            'name->en' => "Program with special chars & symbols {$uniqueSuffix}",
        ]);
    });

    test('study program validates degree field', function () {
        $invalidDegreeData = [
            'name' => ['lt' => 'Test Program'],
            'degree' => str_repeat('a', 256), // Too long
            'tenant_id' => $this->tenant->id,
        ];

        asUser($this->admin)
            ->post(route('studyPrograms.store'), $invalidDegreeData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['degree']);
    });
});
