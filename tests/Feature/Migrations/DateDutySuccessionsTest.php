<?php

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

function dateDutySuccessionsMigration(): object
{
    return require base_path('database/migrations/2026_10_27_120000_add_effective_on_to_duty_successions_table.php');
}

function undatedLink(Duty $predecessor, Duty $successor): int
{
    return DB::table('duty_successions')->insertGetId([
        'predecessor_id' => $predecessor->id,
        'successor_id' => $successor->id,
        'created_at' => '2026-10-20 10:00:00',
        'updated_at' => '2026-10-20 10:00:00',
    ]);
}

function heldOn(Duty $duty, string $start, ?string $end): void
{
    Dutiable::factory()->create([
        'duty_id' => $duty->id,
        'dutiable_id' => User::factory()->create()->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => $start,
        'end_date' => $end,
    ]);
}

test('dates existing links from the successor\'s first term, else the predecessor\'s last, else the link', function (): void {
    dateDutySuccessionsMigration()->down();
    [$old, $new, $quiet, $empty, $lonely] = Duty::factory()->count(5)->create()->all();
    heldOn($new, '2021-07-01', null);
    heldOn($quiet, '2018-07-01', '2020-06-30');

    $fromSuccessor = undatedLink($old, $new);
    $fromPredecessor = undatedLink($quiet, $empty);
    $fromLink = undatedLink($lonely, $empty);

    dateDutySuccessionsMigration()->up();

    expect(DB::table('duty_successions')->pluck('effective_on', 'id')->all())->toMatchArray([
        $fromSuccessor => '2021-07-01',
        $fromPredecessor => '2020-07-01',
        $fromLink => '2026-10-20',
    ]);
});
