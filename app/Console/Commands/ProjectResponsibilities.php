<?php

namespace App\Console\Commands;

use App\Actions\ProjectDutyResponsibilities;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Rebuild duty roles from responsibility grants, or report where they drifted apart')]
#[Signature('responsibilities:project {--check : Report drift without writing}')]
class ProjectResponsibilities extends Command
{
    public function handle(): int
    {
        $drift = ProjectDutyResponsibilities::drift();

        if ($drift === []) {
            $this->info('✅ Duty roles match their responsibility grants.');

            return self::SUCCESS;
        }

        $this->table(['Duty', 'Missing roles', 'Extra roles'], collect($drift)->map(fn (array $row, string $dutyId): array => [
            $dutyId, implode(', ', $row['missing']) ?: '—', implode(', ', $row['extra']) ?: '—',
        ])->values()->all());

        if ($this->option('check')) {
            $this->warn(count($drift).' duties drifted; run without --check to rebuild them.');

            return self::FAILURE;
        }

        $changed = ProjectDutyResponsibilities::forDuties(array_keys($drift));
        $this->info('🔧 Rebuilt '.count($changed).' duties.');

        return self::SUCCESS;
    }
}
