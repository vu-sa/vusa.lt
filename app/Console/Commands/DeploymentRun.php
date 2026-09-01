<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Description('Run full deployment process')]
#[Signature('deployment:run 
                           {--dry-run : Show deployment steps without executing}
                           {--from= : Start from specific step}')]
class DeploymentRun extends Command
{
    /**
     * The ordered deployment pipeline.
     *
     * Order is the contract: everything between `maintenance` and `online` is visible downtime, so a
     * step earns its place there only if it cannot run against a live site. `backup` and `search` sit
     * outside that span deliberately — see their own notes.
     *
     * `DeploymentResume` reads this to work out what comes next, so the two cannot drift.
     *
     * @var array<string, array{name: string, command: string, args?: array<string, mixed>, critical: bool}>
     */
    public const array STEPS = [
        // Outside the maintenance window on purpose. deployment:backup dumps with
        // --single-transaction, which is consistent and non-blocking on InnoDB, so it is safe to run
        // against a live site — and the deploy workflow already does exactly that in its pre-flight
        // phase, before the maintenance page goes up. This step then no-ops via --skip-if-recent.
        // It stays in the pipeline so a hand-run `deployment:run` still takes a backup.
        'backup' => [
            'name' => 'Create database backup',
            'command' => 'deployment:backup',
            'args' => ['--skip-if-recent' => 30],
            'critical' => true,
        ],
        'maintenance' => [
            'name' => 'Enter maintenance mode',
            'command' => 'down',
            // By the time this step runs, the deploy workflow has already put the site behind
            // a scp'd static fallback (storage/framework/maintenance.php, see deploy.yml) —
            // no artisan boot needed for that. This step upgrades it to the real thing:
            // --render bakes this Blade view into storage/framework/maintenance.php (replacing
            // the static fallback), which public/index.php serves before loading Composer.
            // `artisan up` (the `online` step) removes both storage/framework/maintenance.php
            // and storage/framework/down once the deploy finishes.
            'args' => ['--retry' => 60, '--render' => 'errors::maintenance', '--refresh' => 15],
            'critical' => true,
        ],
        'assets' => [
            'name' => 'Deploy assets',
            'command' => 'deployment:deploy-assets',
            'critical' => true,
        ],
        'migrate' => [
            'name' => 'Run database migrations',
            'command' => 'migrate',
            'args' => ['--force' => true],
            'critical' => true,
        ],
        'clear-caches' => [
            'name' => 'Clear stale framework caches',
            'command' => 'optimize:clear',
            'critical' => true,
        ],
        'optimize' => [
            'name' => 'Optimize application',
            'command' => 'optimize',
            'critical' => true,
        ],
        // Must come after `optimize`: optimize:clear runs cache:clear, and both restart signals are
        // cache keys — restarting before it would wipe the signal and leave the workers running the
        // old code. queue:work processes and reverb:start only pick up new code when told to; nothing
        // else in this pipeline tells them, and the deploy has already moved vendor/ out from under
        // them. Supervisor does the actual restart once they exit.
        'workers' => [
            'name' => 'Restart queue workers',
            'command' => 'queue:restart',
            'critical' => true,
        ],
        'online' => [
            'name' => 'Exit maintenance mode',
            'command' => 'up',
            'critical' => true,
        ],
        // After `online`, unlike the queue workers: broadcasting is not needed for the site to serve
        // pages, so a Reverb hiccup should never hold the outage open or fail the deploy.
        'reverb' => [
            'name' => 'Restart Reverb server',
            'command' => 'reverb:restart',
            'critical' => false,
        ],
        // After `online` on purpose. This drops and recreates all 14 Typesense collections and was
        // measured at 53-63s on every deploy — the single largest avoidable chunk of downtime, paid
        // even for a CSS-only change. Running it with the site up trades a full outage for degraded
        // search, and only for the collection currently being rebuilt (1-16s each).
        'search' => [
            'name' => 'Reindex search',
            'command' => 'search:reindex',
            'critical' => false,
        ],
        // Critical so a failure actually reddens the deploy. It is the last step, so failing here
        // cannot strand the site in maintenance mode — and the check itself no longer takes the site
        // down, see DeploymentHealthCheck::handleHealthCheckFailure().
        'health' => [
            'name' => 'Perform health check',
            'command' => 'deployment:health-check',
            'critical' => true,
        ],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $fromStep = $this->option('from');

        if ($dryRun) {
            return $this->showDeploymentPlan($fromStep);
        }

        $this->info('🚀 Starting deployment process...');

        // Clear any existing state if starting from beginning
        if (! $fromStep) {
            $this->clearDeploymentState();
        }

        $startFound = ! $fromStep; // If no from step, start from beginning
        $overallSuccess = true;

        foreach (self::STEPS as $stepKey => $step) {
            // Skip steps until we reach the starting point
            if (! $startFound) {
                if ($stepKey === $fromStep) {
                    $startFound = true;
                } else {
                    continue;
                }
            }

            $this->info("📋 {$step['name']}...");

            try {
                // Record that we're attempting this step
                $this->updateDeploymentState($stepKey, 'running');

                $exitCode = $this->call($step['command'], $step['args'] ?? []);

                if ($exitCode === 0) {
                    $this->info("✅ {$step['name']} completed successfully");
                    $this->updateDeploymentState($stepKey, 'completed');
                } else {
                    throw new \Exception("Command failed with exit code: {$exitCode}");
                }

            } catch (\Exception $e) {
                $this->error("❌ {$step['name']} failed: ".$e->getMessage());
                $this->updateDeploymentState($stepKey, 'failed', $e->getMessage());

                if ($step['critical']) {
                    $overallSuccess = false;

                    // If this isn't the maintenance exit step, try to bring site back online
                    if ($stepKey !== 'online') {
                        $this->error('🚨 Critical step failed - attempting to bring site back online...');
                        $this->call('up');
                    }

                    break;
                } else {
                    $this->warn('⚠️ Non-critical step failed, continuing deployment...');
                    $this->updateDeploymentState($stepKey, 'skipped', $e->getMessage());
                }
            }
        }

        if ($overallSuccess) {
            $this->info('🎉 Deployment completed successfully!');
            $this->clearDeploymentState();

            return 0;
        } else {
            $this->error('💥 Deployment failed. Use "deployment:resume --from=STEP" to continue from the failed step.');

            return 1;
        }
    }

    private function showDeploymentPlan(?string $fromStep): int
    {
        $this->info('📋 Deployment Plan:');
        $this->line('');

        $startFound = ! $fromStep;

        foreach (self::STEPS as $stepKey => $step) {
            if (! $startFound) {
                if ($stepKey === $fromStep) {
                    $startFound = true;
                } else {
                    $this->line("  <fg=gray>⏸ {$step['name']} (skipped)</>");

                    continue;
                }
            }

            $critical = $step['critical'];
            $icon = $critical ? '🔴' : '🟡';
            $type = $critical ? 'critical' : 'non-critical';

            $this->line("  {$icon} {$step['name']} ({$type})");
        }

        $this->line('');
        $this->info('Use "deployment:run" to execute this plan.');

        return 0;
    }

    private function updateDeploymentState(string $step, string $status, ?string $error = null): void
    {
        $state = $this->getDeploymentState();

        $state['steps'][$step] = [
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'error' => $error,
        ];

        $state['last_step'] = $step;
        $state['last_status'] = $status;

        Storage::disk('local')->put('deployment/state.json', json_encode($state, JSON_PRETTY_PRINT));
    }

    private function getDeploymentState(): array
    {
        if (! Storage::disk('local')->exists('deployment/state.json')) {
            return ['steps' => [], 'last_step' => null, 'last_status' => null];
        }

        return json_decode(Storage::disk('local')->get('deployment/state.json'), true) ?? [];
    }

    private function clearDeploymentState(): void
    {
        Storage::disk('local')->delete('deployment/state.json');
    }
}
