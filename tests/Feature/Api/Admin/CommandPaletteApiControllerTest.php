<?php

use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('a guest cannot list personal records', function (): void {
    $this->getJson(route('api.v1.admin.commandPalette.personalRecords'))->assertUnauthorized();
});

test('personal records include only the caller’s eligible institutions and current duties', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $actor = makeUser($tenant);
    $duty = $actor->current_duties()->firstOrFail();
    $assigned = $duty->institution;
    $followed = Institution::factory()->for($tenant)->create(['is_active' => true]);
    $inaccessible = Institution::factory()->for($tenant)->create(['is_active' => false]);
    $secretaried = Institution::factory()->for($tenant)->create();
    $actor->secretariedInstitutions()->attach($secretaried, ['cadence_id' => Cadence::factory()->create()->id]);
    $other = Institution::factory()->for($tenant)->create();
    $endedDuty = Duty::factory()->for($other)->create();
    $actor->duties()->attach($endedDuty, [
        'start_date' => today()->subMonths(2),
        'end_date' => today()->subMonth(),
        'tenant_id' => $tenant->id,
    ]);
    $actor->followedInstitutions()->attach([$assigned->id, $followed->id, $inaccessible->id]);

    $records = asUser($actor)->getJson(route('api.v1.admin.commandPalette.personalRecords'))
        ->assertOk()->assertJsonCount(4, 'data')->json('data');

    expect(collect($records)->pluck('kind', 'id')->all())->toEqual([
        $assigned->id => 'assigned',
        $secretaried->id => 'assigned',
        $followed->id => 'followed',
        $duty->id => 'duty',
    ])->and(collect($records)->firstWhere('id', $duty->id)['href'])->toBe(route('duties.show', $duty, false));
});
