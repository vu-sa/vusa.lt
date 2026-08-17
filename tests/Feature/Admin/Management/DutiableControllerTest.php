<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.create.padalinys',
        'duties.update.padalinys',
        'duties.delete.padalinys',
    ]);

    $this->regularUser = makeUser($this->tenant);
    $this->dutyManager = makeUser($this->tenant);
    $this->dutyManagerDuty = $this->dutyManager->duties()->first();
    $this->dutyManagerDuty->assignRole('Communication Coordinator');

    $this->dutiable = Dutiable::factory()->create([
        'duty_id' => $this->dutyManagerDuty->id,
        'dutiable_id' => $this->regularUser->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay(),
        'additional_email' => null,
    ]);
});

test('cannot update dutiable without permission', function (): void {
    $unauthorizedUser = User::factory()->create();
    $plainDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $dutiableRecord = Dutiable::factory()->create([
        'duty_id' => $plainDuty->id,
        'dutiable_id' => $unauthorizedUser->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay(),
        'additional_email' => null,
    ]);

    $response = asUser($unauthorizedUser)->patch(route('dutiables.update', $dutiableRecord), [
        'additional_email' => 'test@example.com',
    ]);

    $dutiableRecord->refresh();
    expect($response->status())->toBe(403)
        ->and($dutiableRecord->additional_email)->toBeNull();
});

test('duty manager can update dutiable additional_email', function (): void {
    $response = asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
        'additional_email' => 'kontaktas@example.com',
    ]);

    $response->assertRedirect();

    $this->dutiable->refresh();
    expect($this->dutiable->additional_email)->toBe('kontaktas@example.com');
});

test('returns json response for api requests', function (): void {
    $response = asUser($this->dutyManager)
        ->withHeader('Accept', 'application/json')
        ->patch(route('dutiables.update', $this->dutiable), [
            'additional_email' => 'api@example.com',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Pareigybės el. paštas sėkmingai atnaujintas!',
        ]);

    $this->dutiable->refresh();
    expect($this->dutiable->additional_email)->toBe('api@example.com');
});

test('can clear additional_email by sending null', function (): void {
    $this->dutiable->update(['additional_email' => 'existing@example.com']);

    $response = asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
        'additional_email' => null,
    ]);

    $response->assertRedirect();

    $this->dutiable->refresh();
    expect($this->dutiable->additional_email)->toBeNull();
});

test('cannot update dutiable without additional_email field', function (): void {
    $response = asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
        'start_date' => now()->format('Y-m-d'),
    ]);

    $response->assertRedirect();

    $this->dutiable->refresh();
    expect($this->dutiable->additional_email)->toBeNull();
});

test('update request authorizes using dutiable duty relation', function (): void {
    $response = asUser($this->dutyManager)
        ->withHeader('Accept', 'application/json')
        ->patch(route('dutiables.update', $this->dutiable), [
            'additional_email' => 'authorized@example.com',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

describe('study program', function (): void {
    test('duty manager can set the study program shown on the assignment', function (): void {
        $studyProgram = StudyProgram::factory()->forTenant($this->tenant)->create();

        asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
            'study_program_id' => $studyProgram->id,
        ])->assertRedirect();

        expect($this->dutiable->refresh()->study_program_id)->toBe($studyProgram->id);
    });

    test('can clear the study program by sending null', function (): void {
        $this->dutiable->update(['study_program_id' => StudyProgram::factory()->forTenant($this->tenant)->create()->id]);

        asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
            'study_program_id' => null,
        ])->assertRedirect();

        expect($this->dutiable->refresh()->study_program_id)->toBeNull();
    });

    test('rejects a study program id that does not exist', function (): void {
        asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
            'study_program_id' => (string) Str::ulid(),
        ])->assertSessionHasErrors('study_program_id');
    });

    test('edit page scopes the study program list to the duty tenant but keeps an already-selected cross-tenant value', function (): void {
        $ownTenantProgram = StudyProgram::factory()->forTenant($this->tenant)->create();
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherTenantProgram = StudyProgram::factory()->forTenant($otherTenant)->create();

        $this->dutiable->update(['study_program_id' => $otherTenantProgram->id]);

        $response = asUser($this->dutyManager)->get(route('dutiables.edit', $this->dutiable));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/People/EditDutiable')
            ->where('studyPrograms', function ($studyPrograms) use ($ownTenantProgram, $otherTenantProgram) {
                $ids = collect($studyPrograms)->pluck('id')->all();

                return in_array($ownTenantProgram->id, $ids, true)
                    && in_array($otherTenantProgram->id, $ids, true);
            }));
    });

    test('edit page excludes another tenant study program that is not already selected', function (): void {
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherTenantProgram = StudyProgram::factory()->forTenant($otherTenant)->create();

        $response = asUser($this->dutyManager)->get(route('dutiables.edit', $this->dutiable));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/People/EditDutiable')
            ->where('studyPrograms', fn ($studyPrograms) => ! collect($studyPrograms)->pluck('id')->contains($otherTenantProgram->id)));
    });
});

describe('other assignment fields', function (): void {
    test('duty manager can set a per-assignment description', function (): void {
        asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
            'description' => ['lt' => '<p>Atsakingas už komunikaciją</p>', 'en' => ''],
        ])->assertRedirect();

        $this->dutiable->refresh();

        expect($this->dutiable)->toHaveTranslation('description', 'lt')
            ->and($this->dutiable->getTranslation('description', 'lt'))->toContain('Atsakingas už komunikaciją');
    });

    test('duty manager can set additional_photo', function (): void {
        asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
            'additional_photo' => 'contacts/photo.jpg',
        ])->assertRedirect();

        expect($this->dutiable->refresh()->additional_photo)->toBe('contacts/photo.jpg');
    });

    test('duty manager can toggle use_original_duty_name', function (): void {
        // The factory rolls this flag (20% true), so pin the starting state —
        // otherwise the pre-condition below fails on roughly one run in five.
        $this->dutiable->update(['use_original_duty_name' => false]);

        expect($this->dutiable->use_original_duty_name)->toBeFalse();

        asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
            'use_original_duty_name' => true,
        ])->assertRedirect();

        expect($this->dutiable->refresh()->use_original_duty_name)->toBeTrue();
    });

    test('start_date and end_date are validated as real dates', function (): void {
        asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
            'start_date' => '2024-06-01',
            'end_date' => '2024-01-01', // before start_date
        ])->assertSessionHasErrors('end_date');
    });
});
