<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeploymentResume extends Command
{
    protected $signature = 'deployment:resume 
                           {--from= : Resume from specific step (overrides auto-detection)}
                           {--show-state : Show current deployment state}';

    protected $description = 'Resume deployment from the last failed step';

    public function handle(): int
    {
        if ($this->option('show-state')) {
            return $this->showDeploymentState();
        }

        $fromStep = $this->option('from');
        
        if (!$fromStep) {
            $fromStep = $this->detectResumeStep();
            
            if (!$fromStep) {
                $this->error('No deployment state found. Use "deployment:run" to start a new deployment.');
                return 1;
            }
        }

        $this->info("🔄 Resuming deployment from step: {$fromStep}");
        
        // Use the main deployment command with --from option
        return $this->call('deployment:run', ['--from' => $fromStep]);
    }

    private function detectResumeStep(): ?string
    {
        if (!Storage::disk('local')->exists('deployment/state.json')) {
            return null;
        }

        $state = json_decode(Storage::disk('local')->get('deployment/state.json'), true);
        
        if (!$state || !isset($state['steps'])) {
            return null;
        }

        // Find the last failed step or the step after the last completed step
        $lastFailedStep = null;
        $lastCompletedStep = null;
        
        foreach ($state['steps'] as $stepKey => $stepData) {
            if ($stepData['status'] === 'failed') {
                $lastFailedStep = $stepKey;
            } elseif ($stepData['status'] === 'completed') {
                $lastCompletedStep = $stepKey;
            }
        }

        if ($lastFailedStep) {
            return $lastFailedStep;
        }

        // If no failed step but we have completed steps, find next step
        if ($lastCompletedStep) {
            return $this->getNextStep($lastCompletedStep);
        }

        return null;
    }

    private function getNextStep(string $currentStep): ?string
    {
        $steps = [
            'backup' => 'maintenance',
            'maintenance' => 'assets', 
            'assets' => 'migrate',
            'migrate' => 'optimize',
            'optimize' => 'search',
            'search' => 'health',
            'health' => 'online',
            'online' => null, // Last step
        ];

        return $steps[$currentStep] ?? null;
    }

    private function showDeploymentState(): int
    {
        if (!Storage::disk('local')->exists('deployment/state.json')) {
            $this->info('No deployment state found.');
            return 0;
        }

        $state = json_decode(Storage::disk('local')->get('deployment/state.json'), true);
        
        if (!$state || !isset($state['steps'])) {
            $this->info('No deployment steps found in state.');
            return 0;
        }

        $this->info('📊 Current Deployment State:');
        $this->line('');

        foreach ($state['steps'] as $stepKey => $stepData) {
            $status = $stepData['status'];
            $timestamp = $stepData['timestamp'] ?? 'unknown';
            $error = $stepData['error'] ?? null;
            
            $icon = match($status) {
                'completed' => '✅',
                'failed' => '❌',
                'running' => '🔄',
                'skipped' => '⏭️',
                default => '❓'
            };
            
            $this->line("  {$icon} {$stepKey}: {$status} ({$timestamp})");
            
            if ($error) {
                $this->line("      Error: {$error}");
            }
        }

        $this->line('');
        
        if (isset($state['last_step']) && isset($state['last_status'])) {
            $this->info("Last step: {$state['last_step']} ({$state['last_status']})");
        }

        $resumeStep = $this->detectResumeStep();
        if ($resumeStep) {
            $this->info("💡 Use 'deployment:resume' to continue from: {$resumeStep}");
        } else {
            $this->info('✅ No deployment resume needed.');
        }

        return 0;
    }
}