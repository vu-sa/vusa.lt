<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeploymentRun extends Command
{
    protected $signature = 'deployment:run 
                           {--dry-run : Show deployment steps without executing}
                           {--from= : Start from specific step}';

    protected $description = 'Run full deployment process';

    private array $deploymentSteps = [
        'backup' => [
            'name' => 'Create database backup',
            'command' => 'deployment:backup',
            'critical' => true,
        ],
        'maintenance' => [
            'name' => 'Enter maintenance mode',
            'command' => 'down',
            // --render prerenders the view into storage/framework/maintenance.php, which
            // public/index.php serves before loading Composer. Without it, the vendor/ swap
            // in deployment:deploy-assets leaves a window where the app cannot boot at all.
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
        'search' => [
            'name' => 'Reindex search',
            'command' => 'search:reindex',
            'critical' => false, // Non-critical - can continue if fails
        ],
        'online' => [
            'name' => 'Exit maintenance mode',
            'command' => 'up',
            'critical' => true,
            'always_run' => true, // Always attempt to bring site back online
        ],
        'health' => [
            'name' => 'Perform health check',
            'command' => 'deployment:health-check',
            'critical' => false, // Changed to non-critical to avoid breaking deployment
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

        foreach ($this->deploymentSteps as $stepKey => $step) {
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

                if ($step['critical'] ?? true) {
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

        foreach ($this->deploymentSteps as $stepKey => $step) {
            if (! $startFound) {
                if ($stepKey === $fromStep) {
                    $startFound = true;
                } else {
                    $this->line("  <fg=gray>⏸ {$step['name']} (skipped)</>");

                    continue;
                }
            }

            $critical = $step['critical'] ?? true;
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
