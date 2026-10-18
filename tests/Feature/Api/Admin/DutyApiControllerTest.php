<?php

use App\Models\Duty;
use App\Models\DutyName;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

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

test('filters duties by type or no type on initial visits and refreshes', function (bool $untyped, bool $trash): void {
    $institution = $this->manager->duties()->first()->institution;
    $type = DutyType::factory()->create();
    $otherType = DutyType::factory()->create();
    $typed = Duty::factory()->for($institution)->hasAttached($type, [], 'types')->hasAttached($otherType, [], 'types')->create();
    $withoutType = Duty::factory()->for($institution)->create();
    $foreign = Duty::factory()->for(Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail()))->create();
    $expected = $untyped ? $withoutType : $typed;

    if ($trash) {
        $typed->delete();
        $withoutType->delete();
        $foreign->delete();
    }

    $parameters = [
        'filters' => json_encode(['duty_type_id' => $untyped ? 'none' : (string) $type->id]),
        'showDeleted' => $trash ? 'true' : 'false',
    ];
    $apiIds = asUser($this->manager)->getJson(route('api.v1.admin.duties.index', $parameters))
        ->assertOk()->json('data.items.*.id');

    expect($apiIds)->toContain($expected->id)->not->toContain($foreign->id, ($untyped ? $typed : $withoutType)->id);

    asUser($this->manager)->get(route('duties.index', $parameters))
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('duties.data', fn ($items) => collect($items)->pluck('id')->all() === $apiIds)
        ->has('dutyTypes', DutyType::query()->count()));
})->with(['typed' => false, 'untyped' => true])->with(['live' => false, 'trash' => true]);

test('counts duty type facets using the same filters as the results', function (): void {
    $institution = $this->manager->duties()->first()->institution;
    $type = DutyType::factory()->create();
    Duty::factory()->for($institution)->hasAttached($type, [], 'types')->create(['is_active' => false]);
    Duty::factory()->for($institution)->create(['is_active' => false]);
    Duty::factory()->for($institution)->hasAttached($type, [], 'types')->create(['is_active' => true]);

    asUser($this->manager)->getJson(route('api.v1.admin.duties.index', [
        'filters' => json_encode(['data_quality' => 'inactive', 'duty_type_id' => 'none']),
        'include_facets' => 'true',
        'facet_single' => json_encode(['duty_type_id']),
        'facet_values' => json_encode(['duty_type_id' => [(string) $type->id, 'none']]),
    ]))->assertOk()
        ->assertJsonPath('data.facets.duty_type_id.'.$type->id, 1)
        ->assertJsonPath('data.facets.duty_type_id.none', 1);
});

test('returns 403 to a user without duty access', function (): void {
    asUser($this->plainUser)
        ->getJson(route('api.v1.admin.duties.index'))
        ->assertForbidden();
});
