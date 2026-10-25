<?php

use App\Actions\MergeDuties;
use App\Models\Duty;
use App\Models\DutyName;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create([
        'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'],
    ]);
});

function updateDutyPayload(Duty $duty, array $overrides = []): array
{
    return [
        'name' => ['lt' => $duty->getTranslation('name', 'lt'), 'en' => $duty->getTranslation('name', 'en') ?? ''],
        'institution_id' => $duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        ...$overrides,
    ];
}

function holdDuty(Duty $duty, User $user, string $start, ?string $end = null): Dutiable
{
    return Dutiable::factory()->create([
        'duty_id' => $duty->id,
        'dutiable_id' => $user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => $start,
        'end_date' => $end,
    ]);
}

describe('active flag', function (): void {
    test('an empty duty can be marked inactive', function (): void {
        asUser($this->admin)->patch(route('duties.update', $this->duty), updateDutyPayload($this->duty, ['is_active' => false]))
            ->assertSessionHasNoErrors();

        expect($this->duty->fresh()->is_active)->toBeFalse();
    });

    test('a duty someone still holds cannot be marked inactive', function (): void {
        holdDuty($this->duty, User::factory()->create(), now()->subMonth()->toDateString());

        asUser($this->admin)->patch(route('duties.update', $this->duty), updateDutyPayload($this->duty, ['is_active' => false]))
            ->assertSessionHasErrors('is_active');

        expect($this->duty->fresh()->is_active)->toBeTrue();
    });

    test('the member wizard no longer offers an inactive duty', function (): void {
        $this->duty->update(['is_active' => false]);
        $active = Duty::factory()->for($this->institution)->create();

        asUserWithInertia($this->admin)->get(route('duties.updateUsersWizard'))
            ->assertInertia(fn (Assert $page) => $page->where('institutions', function ($institutions) use ($active): bool {
                $dutyIds = collect($institutions)->flatMap(fn ($institution) => collect($institution['duties'] ?? [])->pluck('id'));

                return $dutyIds->contains($active->id) && ! $dutyIds->contains($this->duty->id);
            }));
    });

    test('an inactive duty is not reported as vacant', function (): void {
        $this->duty->update(['is_active' => false]);

        $response = asUser($this->admin)->getJson(route('api.v1.admin.duties.index', ['filters' => json_encode(['data_quality' => 'vacant'])]))->assertOk();

        expect(collect($response->json('data.items'))->pluck('id'))->not->toContain($this->duty->id);
    });
});

describe('former names', function (): void {
    test('are saved with the duty', function (): void {
        asUser($this->admin)->patch(route('duties.update', $this->duty), updateDutyPayload($this->duty, [
            'name' => ['lt' => 'Ryšių su visuomene koordinatorius', 'en' => 'PR Coordinator'],
            'former_names' => [['name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'], 'used_until' => '2026-06-30']],
        ]))->assertSessionHasNoErrors();

        $former = $this->duty->formerNames()->sole();

        expect($former->getTranslation('name', 'lt'))->toBe('Komunikacijos koordinatorius')
            ->and($former->used_until->toDateString())->toBe('2026-06-30');
    });

    test('cannot be dated in the future', function (): void {
        asUser($this->admin)->patch(route('duties.update', $this->duty), updateDutyPayload($this->duty, [
            'former_names' => [['name' => ['lt' => 'Senas'], 'used_until' => now()->addYear()->toDateString()]],
        ]))->assertSessionHasErrors('former_names.0.used_until');
    });

    test('a term reads with the name in force when it began', function (): void {
        DutyName::factory()->for($this->duty)->create(['name' => ['lt' => 'Viešųjų ryšių koordinatorius'], 'used_until' => '2021-06-30']);
        DutyName::factory()->for($this->duty)->create(['name' => ['lt' => 'Informacijos koordinatorius'], 'used_until' => '2018-06-30']);

        app()->setLocale('lt');

        expect($this->duty->nameOn('2017-09-01'))->toBe('Informacijos koordinatorius')
            ->and($this->duty->nameOn('2019-09-01'))->toBe('Viešųjų ryšių koordinatorius')
            ->and($this->duty->nameOn('2024-09-01'))->toBe('Komunikacijos koordinatorius');
    });
});

describe('predecessors', function (): void {
    beforeEach(function (): void {
        $this->combined = Duty::factory()->for($this->institution)->create([
            'name' => ['lt' => 'Socialinis-akademinis koordinatorius', 'en' => 'Social-academic coordinator'],
            'is_active' => false,
        ]);
    });

    test('the duty record lists a linked predecessor and its holders', function (): void {
        holdDuty($this->combined, User::factory()->create(['name' => 'Senas Narys']), '2018-07-01', '2019-06-30');

        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->combined->id])
            ->assertSessionHasNoErrors();

        asUserWithInertia($this->admin)->get(route('duties.show', $this->duty))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('lineage')
                ->loadDeferredProps('dutyPanels', fn (Assert $reload) => $reload
                    ->where('lineage.predecessors.0.id', $this->combined->id)
                    ->where('lineage.predecessorTerms.0.name', 'Senas Narys')
                    ->where('lineage.predecessorTerms.0.duty_name', 'Socialinis-akademinis koordinatorius')));
    });

    test('a duty another tenant owns cannot be linked', function (): void {
        $foreign = Duty::factory()->for(Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->first()))->create();

        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $foreign->id])
            ->assertSessionHasErrors('predecessor_id');

        expect($this->duty->predecessors()->exists())->toBeFalse();
    });

    test('a duty cannot precede itself or its own successor', function (): void {
        $this->combined->predecessors()->attach($this->duty);

        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->duty->id])
            ->assertSessionHasErrors('predecessor_id');
        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->combined->id])
            ->assertSessionHasErrors('predecessor_id');
    });

    test('linking needs the right to edit the duty', function (): void {
        asUser(makeUser($this->tenant))->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->combined->id])
            ->assertForbidden();
    });

    test('unlinking only reaches this duty\'s own links', function (): void {
        $other = Duty::factory()->for($this->institution)->create();
        $other->predecessors()->attach($this->combined);

        asUser($this->admin)->delete(route('duties.predecessors.destroy', [$this->duty, $this->combined]))->assertNotFound();

        expect($other->predecessors()->exists())->toBeTrue();
    });

    test('merging duties carries former names and links over to the kept duty', function (): void {
        $twin = Duty::factory()->for($this->institution)->create();
        DutyName::factory()->for($twin)->create();
        $twin->predecessors()->attach($this->combined);
        $this->duty->predecessors()->attach($this->combined);

        MergeDuties::execute($this->duty, collect([$twin]));

        expect($this->duty->formerNames()->count())->toBe(1)
            ->and($this->duty->predecessors()->pluck('duties.id')->all())->toBe([$this->combined->id])
            ->and(DB::table('duty_successions')->where('successor_id', $twin->id)->exists())->toBeFalse();
    });
});
