<?php

use App\Models\Duty;
use App\Models\DutyName;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Komunikacijos koordinatorius', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.create.padalinys',
        'duties.update.padalinys',
    ]);

    $this->dutyManager = makeUser($this->tenant);
    $this->dutyManagerDuty = $this->dutyManager->duties()->first();
    $this->dutyManagerDuty->assignRole('Komunikacijos koordinatorius');

    $this->institution = $this->dutyManagerDuty->institution;
});

describe('similar', function (): void {
    test('matches an English name in the same institution before considering variants or former names', function (): void {
        $existing = Duty::factory()->for($this->institution)->create([
            'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'],
        ]);
        DutyName::factory()->for($existing)->create([
            'name' => ['lt' => 'Senas pavadinimas', 'en' => 'Communications Coordinator'],
        ]);

        asUser($this->dutyManager)->getJson(route('api.v1.admin.duties.similar', [
            'name' => 'communications coordinator',
            'institution_id' => $this->institution->id,
        ]))->assertOk()
            ->assertJsonCount(1, 'data.same_institution')
            ->assertJsonPath('data.same_institution.0.id', $existing->id)
            ->assertJsonPath('data.same_institution.0.reason', 'same_institution_exact');
    });

    test('matches an English current name in every institution tier', function (string $tier): void {
        $tenant = $tier === 'same_tenant'
            ? $this->tenant
            : Tenant::query()->whereKeyNot($this->tenant->id)->first();
        $existing = Duty::factory()->for(Institution::factory()->for($tenant))->create([
            'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'],
        ]);

        asUser($this->dutyManager)->getJson(route('api.v1.admin.duties.similar', [
            'name' => 'Communications Coordinator',
            'institution_id' => $this->institution->id,
        ]))->assertOk()
            ->assertJsonCount(1, "data.{$tier}")
            ->assertJsonPath("data.{$tier}.0.id", $existing->id)
            ->assertJsonPath("data.{$tier}.0.reason", $tier);
    })->with(['same_tenant', 'other_institution']);

    test('does not return a duty twice when both translations match', function (): void {
        $existing = Duty::factory()->for($this->institution)->create([
            'name' => ['lt' => 'Coordinator', 'en' => 'Coordinator'],
        ]);

        asUser($this->dutyManager)->getJson(route('api.v1.admin.duties.similar', [
            'name' => 'Coordinator',
            'institution_id' => $this->institution->id,
        ]))->assertOk()
            ->assertJsonCount(1, 'data.same_institution')
            ->assertJsonPath('data.same_institution.0.id', $existing->id);
    });

    test('still finds an English former name when neither current translation matches', function (bool $sameInstitution): void {
        $institution = $sameInstitution ? $this->institution : Institution::factory()->for($this->tenant)->create();
        $existing = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
        ]);
        DutyName::factory()->for($existing)->create([
            'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'],
        ]);
        $tier = $sameInstitution ? 'same_institution' : 'same_tenant';

        asUser($this->dutyManager)->getJson(route('api.v1.admin.duties.similar', [
            'name' => 'Communications Coordinator',
            'institution_id' => $this->institution->id,
        ]))->assertOk()
            ->assertJsonCount(1, "data.{$tier}")
            ->assertJsonPath("data.{$tier}.0.id", $existing->id)
            ->assertJsonPath("data.{$tier}.0.reason", 'former_name');
    })->with(['same institution' => true, 'same tenant' => false]);

    test('LIKE metacharacters cannot make an unrelated duty a duplicate', function (string $name): void {
        Duty::factory()->for(Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->first()))->create([
            'name' => ['lt' => 'Koordinatorius', 'en' => 'Coordinator'],
        ]);

        asUser($this->dutyManager)->getJson(route('api.v1.admin.duties.similar', [
            'name' => $name,
            'institution_id' => $this->institution->id,
        ]))->assertOk()
            ->assertJsonCount(0, 'data.other_institution')
            ->assertJsonPath('data.other_institution_count', 0);
    })->with(['Coo%ator', 'Coo_dinator']);

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
            ->and($match['can_manage'])->toBeTrue()
            // Managing is not merging: only super admins merge duties.
            ->and($match['can_merge'])->toBeFalse();
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

    test('the same name in another tenant is informational, not alarming', function (): void {
        $elsewhere = Duty::factory()->create([
            'name' => ['lt' => 'Studentų atstovas', 'en' => 'Studentų atstovas'],
            'institution_id' => Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->first())->create()->id,
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
            'name' => ['lt' => 'Studentų atstovas', 'en' => 'Studentų atstovas'],
            'institution_id' => Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->first()),
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

    test('an inactive duty is still matched, flagged as inactive, with a link to its record', function (): void {
        $retired = Duty::factory()->create([
            'name' => ['lt' => 'Projektų koordinatorius', 'en' => 'Project Coordinator'],
            'institution_id' => $this->institution->id,
            'is_active' => false,
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', [
                'name' => 'Projektų koordinatorius',
                'institution_id' => $this->institution->id,
            ]))
            ->assertStatus(200);

        $match = collect($response->json('data.same_institution'))->firstWhere('id', $retired->id);

        expect($match['is_active'])->toBeFalse()
            ->and($match['can_view'])->toBeTrue();
    });

    test('a duty is found by a name it used to have', function (): void {
        $renamed = Duty::factory()->create([
            'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'],
            'institution_id' => $this->institution->id,
        ]);
        DutyName::factory()->for($renamed)->create(['name' => ['lt' => 'Viešųjų ryšių koordinatorius']]);

        $response = asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', [
                'name' => 'Viešųjų ryšių koordinatorius',
                'institution_id' => $this->institution->id,
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data.same_institution'))->firstWhere('id', $renamed->id)['reason'])->toBe('former_name');
    });

    test('a match elsewhere in the same tenant is named individually, not only counted', function (): void {
        $sibling = Institution::factory()->for($this->tenant)->create();
        $elsewhere = Duty::factory()->create([
            'name' => ['lt' => 'Akademinis koordinatorius', 'en' => 'Academic Coordinator'],
            'institution_id' => $sibling->id,
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(route('api.v1.admin.duties.similar', [
                'name' => 'Akademinis koordinatorius',
                'institution_id' => $this->institution->id,
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data.same_tenant'))->pluck('id'))->toContain($elsewhere->id)
            ->and(collect($response->json('data.other_institution'))->pluck('id'))->not->toContain($elsewhere->id);
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
