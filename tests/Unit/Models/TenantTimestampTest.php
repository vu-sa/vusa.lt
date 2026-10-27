<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

test('new tenants record creation and update times', function (): void {
    $this->travelTo(Carbon::parse('2026-10-27 10:15:00', 'UTC'));
    $tenant = Tenant::factory()->create();

    expect($tenant->created_at->toISOString())->toBe('2026-10-27T10:15:00.000000Z')
        ->and($tenant->updated_at->toISOString())->toBe('2026-10-27T10:15:00.000000Z');
});

test('editing a legacy tenant records an update without inventing its creation date', function (): void {
    $tenant = Tenant::withoutTimestamps(fn () => Tenant::factory()->create());
    $this->travelTo(Carbon::parse('2026-10-27 14:30:00', 'UTC'));

    $tenant->update(['fullname' => 'Updated tenant']);

    expect($tenant->fresh()->created_at)->toBeNull()
        ->and($tenant->updated_at->toISOString())->toBe('2026-10-27T14:30:00.000000Z');
});
