<?php

namespace App\Console\Commands;

use App\Actions\Dutiables\CheckExOfficioIntegrity;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/** Reports integrity findings without repairing seats and caches the result for system monitoring. */
#[Description('Check current ex officio seat integrity without changing assignments')]
#[Signature('duties:check-ex-officio')]
class CheckExOfficioDutiables extends Command
{
    public function handle(): int
    {
        $result = CheckExOfficioIntegrity::execute();
        Cache::forever(CheckExOfficioIntegrity::CACHE_KEY, $result);
        if ($result['count'] > 0) {
            Log::warning('Ex officio integrity check found inconsistencies', [
                'count' => $result['count'], 'kinds' => collect($result['findings'])->countBy('kind')->all(),
            ]);
            $this->table(['Kind', 'Source', 'Seat'], array_map(fn (array $finding) => [
                $finding['kind'], $finding['source_id'], $finding['row_id'] ?? '—',
            ], $result['findings']));

            return self::FAILURE;
        }
        $this->info('Ex officio integrity: no findings.');

        return self::SUCCESS;
    }
}
