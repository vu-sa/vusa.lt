<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.create.padalinys',
        'duties.update.padalinys',
    ]);

    $this->dutyManager = makeUser($this->tenant);
    $this->dutyManagerDuty = $this->dutyManager->duties()->first();
    $this->dutyManagerDuty->assignRole('Communication Coordinator');

    $this->institution = $this->dutyManagerDuty->institution;
});

describe('similar', function (): void {
    test('an exact name in the same institution is the loudest match', function (): void {
        $existing = Duty::factory()->create([
            'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'],
            'institution_id' => $this->institution->id,
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', [
                'name' => 'Komunikacijos koordinatorius',
                'institution_id' => $this->institution->id,
            ]))
            ->assertStatus(200);

        $match = collect($response->json('data.same_institution'))->firstWhere('id', $existing->id);

        expect($match)->not->toBeNull()
            ->and($match['reason'])->toBe('same_institution_exact')
            ->and($match['can_manage'])->toBeTrue();
    });

    test('a gendered twin in the same institution is flagged as a variant, not an exact match', function (): void {
        // The actual bug this endpoint exists to catch: an admin about to create a
        // feminine duty that the app would have inflected automatically.
        $existing = Duty::factory()->create([
            'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'],
            'institution_id' => $this->institution->id,
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', [
                'name' => 'Komunikacijos koordinatorė',
                'institution_id' => $this->institution->id,
            ]))
            ->assertStatus(200);

        $match = collect($response->json('data.same_institution'))->firstWhere('id', $existing->id);

        expect($match)->not->toBeNull()
            ->and($match['reason'])->toBe('same_institution_variant');
    });

    test('the same name in a different institution is informational, not alarming', function (): void {
        $elsewhere = Duty::factory()->create([
            'name' => ['lt' => 'Studentų atstovas', 'en' => 'Student Representative'],
            'institution_id' => Institution::factory()->for($this->tenant)->create()->id,
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', [
                'name' => 'Studentų atstovas',
                'institution_id' => $this->institution->id,
            ]))
            ->assertStatus(200);

        expect($response->json('data.same_institution'))->toBe([])
            ->and(collect($response->json('data.other_institution'))->pluck('id'))->toContain($elsewhere->id)
            ->and($response->json('data.other_institution_count'))->toBeGreaterThanOrEqual(1);
    });

    test('other-institution matches are capped while the count reflects the true total', function (): void {
        Duty::factory()->count(5)->create([
            'name' => ['lt' => 'Studentų atstovas', 'en' => 'Student Representative'],
            'institution_id' => Institution::factory()->for($this->tenant),
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', [
                'name' => 'Studentų atstovas',
                'institution_id' => $this->institution->id,
            ]))
            ->assertStatus(200);

        expect($response->json('data.other_institution'))->toHaveCount(3)
            ->and($response->json('data.other_institution_count'))->toBe(5);
    });

    test('excludes the duty being edited from its own results', function (): void {
        $editing = Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chairperson'],
            'institution_id' => $this->institution->id,
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', [
                'name' => 'Pirmininkas',
                'institution_id' => $this->institution->id,
                'exclude_id' => $editing->id,
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data.same_institution'))->pluck('id'))->not->toContain($editing->id);
    });

    test('an unrelated name in the same institution is not a match', function (): void {
        Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chairperson'],
            'institution_id' => $this->institution->id,
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', [
                'name' => 'Iždininkas',
                'institution_id' => $this->institution->id,
            ]))
            ->assertStatus(200);

        expect($response->json('data.same_institution'))->toBe([]);
    });

    test('a user without duties.create permission is refused', function (): void {
        asUser(makeUser($this->tenant))
            ->getJson(route('api.v1.admin.duties.similar', ['name' => 'Pirmininkas']))
            ->assertStatus(403);
    });

    test('requires a name', function (): void {
        asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', []))
            ->assertStatus(422);
    });
});
