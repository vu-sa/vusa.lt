<?php

use App\Models\Institution;
use App\Models\Programme;
use App\Models\ProgrammeBlock;
use App\Models\ProgrammeDay;
use App\Models\ProgrammePart;
use App\Models\ProgrammeSection;
use App\Models\Tenant;
use App\Models\Training;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Programmes carry no permissions of their own — every mutation is authorized
 * against the training that owns the programme, so these tests hang everything
 * off a training in the acting user's tenant.
 */
function makeProgrammeForTenant(Tenant $tenant): Programme
{
    $training = Training::factory()->for(
        Institution::factory()->state(['tenant_id' => $tenant->id])
    )->create();

    $programme = Programme::factory()->create();
    $training->programmes()->attach($programme);

    return $programme;
}

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $this->user = makeUser($this->tenant);
    $this->trainingManager = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->programme = makeProgrammeForTenant($this->tenant);
    $this->day = ProgrammeDay::factory()->create(['programme_id' => $this->programme->id]);
});

/**
 * A day always carries at least one element — `days.*.elements` is `required`,
 * which an empty array does not satisfy.
 */
function programmePayload(ProgrammeDay $day, ?ProgrammePart $part = null): array
{
    $part ??= ProgrammePart::factory()->create();

    return [
        'days' => [
            [
                'id' => $day->id,
                'title' => ['lt' => 'Diena', 'en' => 'Day'],
                'start_time' => now()->addDay()->toDateTimeString(),
                'elements' => [
                    [
                        'id' => $part->id,
                        'type' => 'part',
                        'title' => ['lt' => 'Dalis', 'en' => 'Part'],
                        'duration' => 30,
                    ],
                ],
            ],
        ],
    ];
}

describe('auth: simple user', function (): void {
    test('cannot update a programme', function (): void {
        asUser($this->user)
            ->put(route('programmes.update', $this->programme), programmePayload($this->day))
            ->assertStatus(403);
    });

    test('cannot destroy a programme day', function (): void {
        asUser($this->user)
            ->delete(route('programmeDays.destroy', $this->day))
            ->assertStatus(403);

        expect(ProgrammeDay::query()->find($this->day->id))->not->toBeNull();
    });

    test('cannot destroy a programme section', function (): void {
        $section = ProgrammeSection::factory()->create(['duration' => 30]);
        $this->day->sections()->attach($section, ['order' => 0]);

        asUser($this->user)
            ->delete(route('programmeSections.destroy', $section))
            ->assertStatus(403);

        expect(ProgrammeSection::query()->find($section->id))->not->toBeNull();
    });

    test('cannot destroy a programme block', function (): void {
        $section = ProgrammeSection::factory()->create(['duration' => 30]);
        $this->day->sections()->attach($section, ['order' => 0]);
        $block = ProgrammeBlock::factory()->create(['programme_section_id' => $section->id]);

        asUser($this->user)
            ->delete(route('programmeBlocks.destroy', $block))
            ->assertStatus(403);

        expect(ProgrammeBlock::query()->find($block->id))->not->toBeNull();
    });

    test('cannot destroy a programme part', function (): void {
        $part = ProgrammePart::factory()->create();
        $this->day->parts()->attach($part, ['order' => 0]);

        asUser($this->user)
            ->delete(route('programmeParts.destroy', $part))
            ->assertStatus(403);

        expect(ProgrammePart::query()->find($part->id))->not->toBeNull();
    });

    test('cannot attach a section to a day', function (): void {
        $section = ProgrammeSection::factory()->create(['duration' => 30]);

        asUser($this->user)
            ->post(route('programmeSections.attach', $section), [
                'programmeDay' => $this->day->id,
                'order' => 0,
            ])
            ->assertStatus(403);

        expect($section->programmeDays()->count())->toBe(0);
    });

    test('cannot detach a part from a day', function (): void {
        $part = ProgrammePart::factory()->create();
        $this->day->parts()->attach($part, ['order' => 0]);

        asUser($this->user)
            ->post(route('programmeParts.detach', $part), ['programmeDay' => $this->day->id])
            ->assertStatus(403);

        expect($part->programmeDays()->count())->toBe(1);
    });
});

describe('auth: user who can update the owning training', function (): void {
    test('can update the programme', function (): void {
        asUser($this->trainingManager)
            ->put(route('programmes.update', $this->programme), programmePayload($this->day))
            ->assertRedirect();

        expect($this->day->fresh()->getTranslation('title', 'lt'))->toBe('Diena');
    });

    test('can destroy a programme day', function (): void {
        asUser($this->trainingManager)
            ->delete(route('programmeDays.destroy', $this->day))
            ->assertRedirect();

        expect(ProgrammeDay::query()->find($this->day->id))->toBeNull();
    });

    test('can attach a section to a day', function (): void {
        $section = ProgrammeSection::factory()->create(['duration' => 30]);

        asUser($this->trainingManager)
            ->post(route('programmeSections.attach', $section), [
                'programmeDay' => $this->day->id,
                'order' => 0,
            ])
            ->assertRedirect();

        expect($section->programmeDays()->count())->toBe(1);
    });
});

describe('cross-programme isolation', function (): void {
    test('cannot pull another programmes day into a programme it may edit', function (): void {
        $foreignProgramme = makeProgrammeForTenant($this->tenant);
        $foreignDay = ProgrammeDay::factory()->create(['programme_id' => $foreignProgramme->id]);

        asUser($this->trainingManager)
            ->put(route('programmes.update', $this->programme), programmePayload($foreignDay))
            ->assertStatus(403);

        expect($foreignDay->fresh()->programme_id)->toBe($foreignProgramme->id);
    });

    test('cannot destroy a day belonging to a training in another tenant', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $foreignProgramme = makeProgrammeForTenant($otherTenant);
        $foreignDay = ProgrammeDay::factory()->create(['programme_id' => $foreignProgramme->id]);

        asUser($this->trainingManager)
            ->delete(route('programmeDays.destroy', $foreignDay))
            ->assertStatus(403);

        expect(ProgrammeDay::query()->find($foreignDay->id))->not->toBeNull();
    });

    test('a programme with no owning training cannot be mutated at all', function (): void {
        $orphan = Programme::factory()->create();
        $orphanDay = ProgrammeDay::factory()->create(['programme_id' => $orphan->id]);

        asUser($this->trainingManager)
            ->delete(route('programmeDays.destroy', $orphanDay))
            ->assertStatus(403);
    });
});
