<?php

use App\Models\Duty;
use App\Models\DutyName;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->manager = makeUser($this->tenant);
    $this->manager->duties()->first()->assignRole('Komunikacijos koordinatorius');
    $this->plainUser = makeUser($this->tenant);
});

test('returns the same paginated duty collection contract as the first page', function (): void {
    Duty::factory()->for($this->manager->duties()->first()->institution)->count(3)->create();

    asUser($this->manager)
        ->getJson(route('api.v1.admin.duties.index', ['per_page' => 2]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.items')
        ->assertJsonPath('data.per_page', 2)
        ->assertJsonStructure(['data' => ['items', 'total', 'per_page', 'current_page', 'last_page']]);
});

test('applies the collection data-quality filter', function (): void {
    $vacant = Duty::factory()->for($this->manager->duties()->first()->institution)->create();

    asUser($this->manager)
        ->getJson(route('api.v1.admin.duties.index', ['filters' => json_encode(['data_quality' => 'vacant'])]))
        ->assertOk()
        ->assertJsonPath('data.items.0.id', $vacant->id);
});

test('possible duplicates are same-institution name variants and renamed twins only', function (): void {
    $institution = $this->manager->duties()->first()->institution;
    $named = fn (string $lt, ?Institution $in = null) => Duty::factory()->for($in ?? $institution)
        ->create(['name' => ['lt' => $lt, 'en' => $lt]]);

    $masculine = $named('Komunikacijos koordinatorius');
    $feminine = $named('Komunikacijos koordinatorė');
    $secretary = $named('Sekretorius');
    $renamed = $named('Raštvedys');
    DutyName::factory()->create(['duty_id' => $renamed->id, 'name' => ['lt' => 'Sekretorė', 'en' => 'Secretary']]);
    $named('Iždininkas');
    $named('Komunikacijos koordinatorius', Institution::factory()->for($this->tenant)->create());

    $ids = asUser($this->manager)
        ->getJson(route('api.v1.admin.duties.index', ['filters' => json_encode(['data_quality' => 'possible_duplicates'])]))
        ->assertOk()
        ->json('data.items.*.id');

    expect($ids)->toEqualCanonicalizing([$masculine->id, $feminine->id, $secretary->id, $renamed->id]);
});

test('returns 403 to a user without duty access', function (): void {
    asUser($this->plainUser)
        ->getJson(route('api.v1.admin.duties.index'))
        ->assertForbidden();
});
