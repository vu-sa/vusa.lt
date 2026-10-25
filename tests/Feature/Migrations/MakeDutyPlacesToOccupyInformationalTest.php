<?php

use App\Models\Duty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('clears every old seat count without changing duty timestamps or lifecycle state', function (): void {
    $active = Duty::factory()->create(['places_to_occupy' => 38]);
    $inactive = Duty::factory()->create(['places_to_occupy' => 1, 'is_active' => false]);
    $trashed = Duty::factory()->create(['places_to_occupy' => 5]);
    $trashed->delete();
    $blank = Duty::factory()->create(['places_to_occupy' => null]);
    $before = DB::table('duties')->orderBy('id')->get()->keyBy('id');

    $migration = require base_path('database/migrations/2026_10_25_153106_make_duty_places_to_occupy_informational.php');
    $migration->up();

    foreach ([$active, $inactive, $trashed, $blank] as $duty) {
        $after = DB::table('duties')->where('id', $duty->id)->first();
        expect($after->places_to_occupy)->toBeNull()
            ->and($after->created_at)->toBe($before[$duty->id]->created_at)
            ->and($after->updated_at)->toBe($before[$duty->id]->updated_at)
            ->and($after->deleted_at)->toBe($before[$duty->id]->deleted_at)
            ->and($after->is_active)->toBe($before[$duty->id]->is_active);
    }

    expect(Duty::factory()->create()->fresh()->places_to_occupy)->toBeNull();
});

test('rollback restores the old default without reconstructing cleared reference counts', function (): void {
    $duty = Duty::factory()->create(['places_to_occupy' => 38]);
    $migration = require base_path('database/migrations/2026_10_25_153106_make_duty_places_to_occupy_informational.php');
    $migration->up();

    try {
        $migration->down();

        expect($duty->fresh()->places_to_occupy)->toBeNull()
            ->and(Duty::factory()->create()->fresh()->places_to_occupy)->toBe(1);
    } finally {
        $migration->up();
    }
});
