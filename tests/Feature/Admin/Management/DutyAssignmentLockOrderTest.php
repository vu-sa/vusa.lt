<?php

use App\Actions\Dutiables\ReconcileExOfficioSeats;
use App\Actions\MergeUsers;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

function dutyAssignmentLockQueries(Closure $write): array
{
    $connection = DB::connection();
    $grammar = $connection->getQueryGrammar();
    if ($grammar instanceof SQLiteGrammar) {
        // SQLite ignores row locks, so mark the statements that request one.
        $connection->setQueryGrammar(new class($connection) extends SQLiteGrammar
        {
            protected function compileLock(Builder $query, $value)
            {
                return $value === true ? '/* for update */' : '';
            }
        });
    }

    $wasLogging = $connection->logging();
    $previousEntries = count($connection->getQueryLog());
    $connection->enableQueryLog();

    try {
        $write();

        return collect($connection->getQueryLog())->slice($previousEntries)
            ->filter(fn (array $entry) => str_contains($entry['query'], 'for update'))
            ->map(fn (array $entry) => str_replace(['"', chr(96)], '', $entry['query']))
            ->values()->all();
    } finally {
        if (! $wasLogging) {
            $connection->disableQueryLog();
            $connection->flushQueryLog();
        }
        $connection->setQueryGrammar($grammar);
    }
}

test('merging users locks their affected institutions and duties before their terms', function (): void {
    $kept = User::factory()->create(['profile_photo_path' => null]);
    $source = User::factory()->create(['profile_photo_path' => null]);
    foreach (Duty::factory()->count(2)->create() as $duty) {
        Dutiable::factory()->forDuty($duty)->forUser($source)->active()->create([
            'start_date' => today()->subMonth(),
            'additional_photo' => null,
        ]);
    }

    $locks = dutyAssignmentLockQueries(fn () => MergeUsers::execute($kept, collect([$source]), $kept->email));

    expect($locks[0] ?? '')->toContain('from institutions', 'in (?, ?)', 'order by id asc')
        ->and($locks[1] ?? '')->toContain('from duties')
        ->and($locks[2] ?? '')->toContain('from dutiables');
});

test('ex officio reconciliation locks institutions before target duties and derived terms', function (): void {
    $source = Dutiable::factory()->active()->create(['start_date' => today()->subMonth()]);
    $target = Duty::factory()->create();
    $source->duty->exOfficioTargetDuties()->attach($target);

    $locks = dutyAssignmentLockQueries(fn () => ReconcileExOfficioSeats::forSource($source));

    expect($locks[0] ?? '')->toContain('from institutions', 'in (?, ?)', 'order by id asc')
        ->and($locks[1] ?? '')->toContain('from duties')
        ->and($locks[2] ?? '')->toContain('from dutiables')
        ->and($target->dutiables()->where('via_dutiable_id', $source->id)->exists())->toBeTrue();
});
