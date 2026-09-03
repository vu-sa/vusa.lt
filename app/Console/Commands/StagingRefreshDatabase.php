<?php

namespace App\Console\Commands;

use App\Services\StagingIsolationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

#[Description('Replace the staging database with the newest production backup, scrubbed')]
#[Signature('staging:refresh-database
                           {--source= : Directory holding production backups (default: config)}
                           {--scrub-only : Scrub and empty tables in place, without importing}
                           {--skip-reindex : Skip the Typesense reindex}')]
class StagingRefreshDatabase extends Command
{
    /**
     * Emptied after import. None of it is worth carrying across, and the first three are actively
     * dangerous: a restored queue would replay production jobs, and restored notifications would let
     * staging's schedule act on production's delivery state.
     *
     * @var list<string>
     */
    private const array DISPOSABLE_TABLES = [
        'jobs',
        'job_batches',
        'failed_jobs',
        'notifications',
        'notification_digest_queue',
        'push_subscriptions',
        'sessions',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'activity_log',
    ];

    public function handle(StagingIsolationService $isolation): int
    {
        // The only thing standing between this command and dropping the production database. It is
        // deliberately not overridable: there is no --force, and no prompt a tired person can accept
        // at 2am. If you need this elsewhere, change the environment, not the guard.
        if (config('app.env') !== 'staging') {
            $this->error('Refused: staging:refresh-database only runs when APP_ENV=staging.');
            $this->line('  Current environment: '.config('app.env'));

            return self::FAILURE;
        }

        $databaseErrors = $isolation->databaseErrors();

        if ($databaseErrors !== []) {
            $this->error('Refused: the staging database target is not verified.');

            foreach ($databaseErrors as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        // --scrub-only re-runs just the sanitising half against whatever is already there, for when
        // a database was restored by hand and needs the same treatment before anyone logs in.
        if (! $this->option('scrub-only')) {
            $backup = $this->findNewestBackup();

            if ($backup === null) {
                return self::FAILURE;
            }

            $this->info('Restoring from '.$backup);

            if (! $this->dropAllTables() || ! $this->import($backup)) {
                return self::FAILURE;
            }
        }

        $this->scrubPersonalData();
        $this->emptyDisposableTables();

        // The point of the whole exercise: dev-branch migrations get exercised nightly against real
        // production data, which is where migration bugs actually surface.
        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        $this->call('optimize:clear');

        if (! $this->option('skip-reindex')) {
            $this->call('search:reindex');
        }

        $this->info('✅ Staging database refreshed.');

        return self::SUCCESS;
    }

    private function findNewestBackup(): ?string
    {
        $directory = $this->option('source') ?: config('app.staging_refresh.source_backup_dir');

        if (! is_string($directory) || $directory === '') {
            $this->error('No source directory configured. Set STAGING_SOURCE_BACKUP_DIR or pass --source.');

            return null;
        }

        // Both sites have a storage/backups, so a mistyped path silently restores staging from its
        // own dumps — a no-op that looks like a successful refresh and hides that production data
        // never arrived. There is no legitimate reason for the source to be this app's own directory.
        if (realpath($directory) === realpath(storage_path('backups'))) {
            $this->error('Refused: the source directory is this application\'s own storage/backups.');
            $this->line('  Point STAGING_SOURCE_BACKUP_DIR at production\'s backups directory instead.');

            return null;
        }

        $backups = glob(rtrim($directory, '/').'/backup_*.sql.gz') ?: [];

        if ($backups === []) {
            $this->error("No backup_*.sql.gz files found in {$directory}");

            return null;
        }

        usort($backups, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return $backups[0];
    }

    /**
     * Drop the tables rather than the database: the staging database user does not necessarily hold
     * DROP DATABASE, and recreating it would lose the grants.
     */
    private function dropAllTables(): bool
    {
        $tables = Schema::getTableListing(schemaQualified: false);

        if ($tables === []) {
            return true;
        }

        $this->info('Dropping '.count($tables).' existing tables...');

        try {
            Schema::withoutForeignKeyConstraints(function () use ($tables): void {
                foreach ($tables as $table) {
                    Schema::drop($table);
                }
            });
        } catch (\Throwable $e) {
            $this->error('Failed to drop tables: '.$e->getMessage());

            return false;
        }

        return true;
    }

    private function import(string $backup): bool
    {
        /** @var array<string, mixed> $database */
        $database = config('database.connections.'.config('database.default'));

        $defaultsFile = $this->writeDefaultsFile($database);

        try {
            $script = sprintf(
                'set -eo pipefail; gzip -cd %s | mysql --defaults-extra-file=%s %s',
                escapeshellarg($backup),
                escapeshellarg($defaultsFile),
                escapeshellarg((string) $database['database']),
            );

            $process = new Process(['bash', '-c', $script]);
            $process->setTimeout(null);
            $process->run();

            if (! $process->isSuccessful()) {
                $this->error('Import failed: '.trim($process->getErrorOutput()));

                return false;
            }
        } finally {
            @unlink($defaultsFile);
        }

        return true;
    }

    /**
     * Staging inherits production's real student data. Even behind basic auth, the addresses are the
     * live risk: staging's scheduler runs notifications:send-news every 15 minutes and meeting
     * reminders every 30, so a real address in this table is a real email to a real student.
     * MAIL_MAILER=log is the other half of that guard; this is the half that survives a misconfigured
     * .env.
     */
    private function scrubPersonalData(): void
    {
        $allowlist = $this->emailAllowlist();
        $scrubbed = 0;

        // Chunked in PHP rather than one UPDATE ... CONCAT(): SQLite has no CONCAT, and keeping this
        // driver-agnostic is what lets the test suite prove the scrub actually happens. A few
        // thousand rows once a night is not worth a portability hole in the one piece of this
        // command that protects real people's contact details.
        DB::table('users')
            ->select('id', 'email')
            ->whereNotIn('email', $allowlist)
            ->orderBy('id')
            ->chunkById(500, function ($users) use (&$scrubbed): void {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'email' => 'user'.$user->id.'@staging.invalid',
                        'phone' => null,
                        'remember_token' => null,
                    ]);
                    $scrubbed++;
                }
            });

        $this->info("Scrubbed {$scrubbed} user record(s); ".count($allowlist).' address(es) kept.');
    }

    /**
     * @return list<string>
     */
    private function emailAllowlist(): array
    {
        $configured = config('app.staging_refresh.email_allowlist', []);

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        return array_values(array_filter(array_map(trim(...), (array) $configured)));
    }

    private function emptyDisposableTables(): void
    {
        $emptied = [];

        foreach (self::DISPOSABLE_TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $emptied[] = $table;
            }
        }

        if ($emptied !== []) {
            $this->info('Emptied: '.implode(', ', $emptied));
        }
    }

    /**
     * @param  array<string, mixed>  $database
     */
    private function writeDefaultsFile(array $database): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dbrestore');
        chmod($path, 0600);

        $lines = [
            '[client]',
            'host='.($database['host'] ?? 'localhost'),
            'port='.($database['port'] ?? 3306),
            'user='.($database['username'] ?? ''),
        ];

        if (! empty($database['password'])) {
            $lines[] = 'password="'.$database['password'].'"';
        }

        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);

        return $path;
    }
}
