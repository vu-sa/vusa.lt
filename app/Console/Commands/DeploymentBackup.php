<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

#[Description('Create a timestamped, compressed MySQL backup')]
#[Signature('deployment:backup
                           {--skip-if-recent= : Skip if a backup newer than this many minutes exists}
                           {--keep-days=7 : Delete backups older than this}')]
class DeploymentBackup extends Command
{
    /**
     * Tables whose *data* is disposable — dumped as schema only.
     *
     * Measured on production: of 628 MB, telescope_entries alone is 334 MB (53%). Excluding these
     * takes the dump from ~450 MB to ~60 MB before compression. `activity_log` is deliberately NOT
     * here — it is real audit data; it is kept in the dump and pruned on retention instead
     * (`activitylog:clean`, config/activitylog.php `clean_after_days`).
     *
     * @var list<string>
     */
    private const array SCHEMA_ONLY_TABLES = [
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function handle(): int
    {
        $backupDir = storage_path('backups');

        if (! is_dir($backupDir) && ! mkdir($backupDir, 0755, true) && ! is_dir($backupDir)) {
            $this->error('Failed to create backup directory: '.$backupDir);

            return self::FAILURE;
        }

        if ($recent = $this->findRecentBackup($backupDir)) {
            $this->info("Skipping backup — {$recent} is newer than {$this->option('skip-if-recent')} minutes.");

            return self::SUCCESS;
        }

        $connection = config('database.default');

        /** @var array<string, mixed> $database */
        $database = config("database.connections.{$connection}");

        $dbName = is_string($database['database'] ?? null) ? $database['database'] : '';
        $username = is_string($database['username'] ?? null) ? $database['username'] : '';

        if ($dbName === '' || $username === '') {
            $this->error('Database configuration is incomplete');

            return self::FAILURE;
        }

        $target = $backupDir.'/backup_'.now()->format('Ymd_His').'.sql.gz';
        $temp = $target.'.tmp';

        // Credentials go in a 0600 file, never on the argv: mysqldump's -p<pass> is visible in `ps`
        // to every other user on the box.
        $defaultsFile = $this->writeDefaultsFile($database);

        try {
            $this->info('Creating database backup...');

            if (! $this->dump($defaultsFile, $dbName, $temp)) {
                @unlink($temp);

                return self::FAILURE;
            }

            if (! $this->isComplete($temp)) {
                $this->error('Backup is truncated — mysqldump did not write its completion marker.');
                @unlink($temp);

                return self::FAILURE;
            }

            // Only now does the file get its real name, so a crashed dump can never be mistaken for
            // a good backup (by the retention sweep, by --skip-if-recent, or by a human restoring).
            rename($temp, $target);
        } finally {
            @unlink($defaultsFile);
        }

        $megabytes = round(filesize($target) / 1024 / 1024, 1);
        $this->info("Database backup created: {$target} ({$megabytes} MB)");

        $this->cleanupOldBackups($backupDir);

        return self::SUCCESS;
    }

    /**
     * Two passes into one gzip stream: everything with data, then the disposable tables as schema
     * only, so a restore still rebuilds a complete database.
     *
     * --single-transaction takes a consistent snapshot without locking InnoDB tables, which is what
     * makes it safe to run this against a live site — the deploy relies on that to keep the backup
     * out of the maintenance window.
     */
    private function dump(string $defaultsFile, string $dbName, string $temp): bool
    {
        $common = [
            '--defaults-extra-file='.$defaultsFile,
            '--single-transaction',
            '--quick',
            '--routines',
            '--events',
            '--no-tablespaces',
        ];

        // Only tables that actually exist. mysqldump tolerates --ignore-table for a missing table,
        // but naming one explicitly in the --no-data pass aborts the whole dump ("Couldn't find
        // table"), and this list covers optional tables — `cache` and `sessions` are absent whenever
        // those drivers are Redis, as they are here.
        $schemaOnlyTables = array_values(array_filter(
            self::SCHEMA_ONLY_TABLES,
            fn (string $table): bool => Schema::hasTable($table),
        ));

        $ignore = array_map(
            fn (string $table): string => "--ignore-table={$dbName}.{$table}",
            $schemaOnlyTables,
        );

        $passes = [$this->shellCommand(['mysqldump', ...$common, ...$ignore, $dbName])];

        // Guard the empty case: `mysqldump --no-data <db>` with no table list dumps the schema of
        // *every* table, duplicating the whole structure into the archive.
        if ($schemaOnlyTables !== []) {
            $passes[] = $this->shellCommand(
                ['mysqldump', ...$common, '--no-data', $dbName, ...$schemaOnlyTables]
            );
        }

        // bash, not sh: `pipefail` stops gzip's happy exit status from masking a mysqldump failure,
        // and `-e` is what stops the *first* pass's failure from being masked by the second's success.
        // Without -e, `{ failing_dump; schema_dump; }` exits with the schema dump's status — so a data
        // dump that died halfway produced a truncated archive that still ended in "-- Dump completed"
        // and passed every check below. Verified: `bash -c 'set -o pipefail; { false; echo x; } | cat'`
        // exits 0; adding -e exits 1.
        $script = sprintf(
            'set -eo pipefail; { %s; } | gzip -c > %s',
            implode('; ', $passes),
            escapeshellarg($temp),
        );

        $process = new Process(['bash', '-c', $script]);
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Backup failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));

            return false;
        }

        return true;
    }

    /**
     * @param  list<string>  $arguments
     */
    private function shellCommand(array $arguments): string
    {
        return implode(' ', array_map(escapeshellarg(...), $arguments));
    }

    /**
     * mysqldump ends every complete dump with "-- Dump completed". Checking for it catches a dump
     * that died a third of the way through — which a plain "is the file non-empty?" test passes.
     */
    private function isComplete(string $path): bool
    {
        $verify = Process::fromShellCommandline('gzip -cd '.escapeshellarg($path).' | tail -c 200');
        $verify->setTimeout(300);
        $verify->run();

        return $verify->isSuccessful() && str_contains($verify->getOutput(), '-- Dump completed');
    }

    /**
     * @param  array<string, mixed>  $database
     */
    private function writeDefaultsFile(array $database): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dbdump');
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

    private function findRecentBackup(string $backupDir): ?string
    {
        $minutes = $this->option('skip-if-recent');

        if ($minutes === null || ! is_numeric($minutes)) {
            return null;
        }

        $cutoff = now()->subMinutes((int) $minutes)->timestamp;

        foreach (glob($backupDir.'/backup_*.sql.gz') ?: [] as $file) {
            if (filemtime($file) >= $cutoff) {
                return basename($file);
            }
        }

        return null;
    }

    private function cleanupOldBackups(string $backupDir): void
    {
        $cutoff = now()->subDays((int) $this->option('keep-days'))->timestamp;
        $deleted = 0;

        // The .sql glob keeps sweeping up the uncompressed backups written before this command
        // learned to compress.
        foreach (glob($backupDir.'/backup_*.sql{,.gz}', GLOB_BRACE) ?: [] as $file) {
            if (filemtime($file) < $cutoff && unlink($file)) {
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} old backup file(s)");
        }
    }
}
