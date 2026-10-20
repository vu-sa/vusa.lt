<?php

namespace App\Console\Commands;

use App\Actions\Permissions\SyncPermissionCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Safe to run on every deploy: existing grantable permissions keep their ids and holders.
 */
#[Description('Create the permissions the catalog offers and remove the ones it no longer does')]
#[Signature('permissions:sync {--dry-run : Report what would change without writing}')]
class SyncPermissions extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = SyncPermissionCatalog::execute($dryRun);

        $verb = $dryRun ? 'Would create' : 'Created';
        $this->info("{$verb} ".count($result['created']).' permissions');

        foreach ($result['created'] as $name) {
            $this->line("  + {$name}", verbosity: OutputInterface::VERBOSITY_VERBOSE);
        }

        $this->info(($dryRun ? 'Would remove ' : 'Removed ').count($result['removed']).' permissions');

        if ($result['removed'] !== []) {
            $this->table(
                ['Permission', 'Held by'],
                array_map(fn (array $row): array => [$row['name'], implode(', ', $row['held_by']) ?: '—'], $result['removed']),
            );
        }

        return self::SUCCESS;
    }
}
