<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create();
});

test('unauthenticated request is rejected', function (): void {
    $this->getJson(route('api.v1.admin.institutions.memberTerms', $this->institution))->assertStatus(401);
});

test('a viewer who cannot open the institution gets 403', function (): void {
    asUser(makeUser($this->tenant))
        ->getJson(route('api.v1.admin.institutions.memberTerms', $this->institution))
        ->assertStatus(403);
});

test('lists current and former terms with their dates', function (): void {
    Dutiable::factory()->forDuty($this->duty)->active()->create(['start_date' => '2025-09-01']);
    Dutiable::factory()->forDuty($this->duty)->create(['start_date' => '2020-09-01', 'end_date' => '2021-09-01']);

    $terms = asUser(makeAdminUser($this->tenant))
        ->getJson(route('api.v1.admin.institutions.memberTerms', $this->institution))
        ->assertOk()
        ->json('data');

    expect($terms)->toHaveCount(2)
        ->and($terms[0])->toHaveKeys(['name', 'start_date', 'end_date'])
        ->and(collect($terms)->pluck('end_date')->filter())->toHaveCount(1);
});

test('the search document counts people once and holds no term history', function (): void {
    Dutiable::factory()->forDuty($this->duty)->active()->create();
    Dutiable::factory()->forDuty($this->duty)->create(['start_date' => '2020-09-01', 'end_date' => '2021-09-01']);

    $document = $this->institution->fresh()->toSearchableArray();

    expect($document['members_total'])->toBe(2)
        ->and($document['current_user_names'])->toHaveCount(1);
});
