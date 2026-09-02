<?php

use App\Console\Commands\DeploymentBackup;
use Illuminate\Support\Facades\File;

/**
 * The backup is only worth having if a broken one announces itself. These pin the two properties
 * that decide whether a bad dump gets stored as if it were good.
 */
describe('failure is not swallowed', function () {
    // The dump is two mysqldump passes into one gzip stream: table data, then the disposable tables
    // as schema only. `{ a; b; }` exits with b's status, so without `set -e` a data pass that died
    // halfway was masked by the schema pass succeeding — and the resulting truncated archive still
    // ended in "-- Dump completed", so the completion check passed too.
    it('aborts the dump when the first pass fails', function (): void {
        $source = File::get(app_path('Console/Commands/DeploymentBackup.php'));

        expect($source)->toContain('set -eo pipefail');
    });

    // Verifies the shell contract the line above depends on, rather than trusting it: this is the
    // exact difference between storing a truncated backup and refusing it.
    it('relies on shell semantics that actually behave that way', function (): void {
        $withoutE = Process::run(['bash', '-c', 'set -o pipefail; { false; echo second; } | cat']);
        $withE = Process::run(['bash', '-c', 'set -eo pipefail; { false; echo second; } | cat']);

        expect($withoutE->exitCode())->toBe(0)
            ->and($withE->exitCode())->not->toBe(0);
    });

    it('names the archive only after the completion marker is verified', function (): void {
        $source = File::get(app_path('Console/Commands/DeploymentBackup.php'));

        expect($source)->toContain('-- Dump completed')
            ->and(strpos($source, 'isComplete($temp)'))
            ->toBeLessThan(strpos($source, 'rename($temp, $target)'));
    });
});

describe('what the archive contains', function () {
    // Telescope is 53% of the production database and activity_log another 32%. Dropping Telescope's
    // rows takes the dump from ~450 MB to ~11 MB; keeping activity_log's is deliberate, because it is
    // audit data rather than debug residue and is pruned on retention instead.
    it('keeps audit data and drops only debug residue', function (): void {
        $reflection = new ReflectionClass(DeploymentBackup::class);
        $schemaOnly = $reflection->getConstant('SCHEMA_ONLY_TABLES');

        expect($schemaOnly)->toContain('telescope_entries')
            ->and($schemaOnly)->not->toContain('activity_log')
            ->and($schemaOnly)->not->toContain('users');
    });

    // A password on the argv is readable via `ps` by every other account on the shared VPS.
    it('never puts the database password on the command line', function (): void {
        $source = File::get(app_path('Console/Commands/DeploymentBackup.php'));

        expect($source)->toContain('--defaults-extra-file=')
            ->and($source)->not->toContain("'-p'.");
    });
});
