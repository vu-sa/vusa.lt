<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DeploymentHealthCheck extends Command
{
    protected $signature = 'deployment:health-check 
                            {--url= : The URL to check (defaults to APP_URL)}
                            {--timeout=30 : Request timeout in seconds}
                            {--retries=3 : Number of retry attempts}';

    protected $description = 'Perform health check after deployment';

    public function handle(): int
    {
        $url = $this->option('url') ?: config('app.url');
        $timeout = (int) $this->option('timeout');
        $retries = (int) $this->option('retries');

        $this->info("Performing health check on: {$url}");

        // Wait a moment for the site to fully come online
        $this->info('Waiting 5 seconds for site to initialize...');
        sleep(5);

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $this->info("Health check attempt {$attempt} of {$retries}...");

                $response = Http::timeout($timeout)
                    ->withOptions([
                        'verify' => false, // Skip SSL verification for local/staging
                        'allow_redirects' => true,
                    ])
                    ->get($url);

                if ($response->successful()) {
                    $this->info("✅ Health check passed - Site returned HTTP {$response->status()}");

                    // Additional basic content validation
                    $content = $response->body();
                    if (str_contains($content, '<html') && str_contains($content, '</html>')) {
                        $this->info('✅ Content validation passed - Valid HTML structure detected');

                        return 0;
                    } else {
                        $this->warn('⚠️  Content validation warning - Response may not contain valid HTML');

                        // Still consider it successful if HTTP status is OK
                        return 0;
                    }
                } else {
                    $this->warn("⚠️  Health check attempt {$attempt} failed - HTTP {$response->status()}");

                    if ($attempt < $retries) {
                        $this->info('Waiting 10 seconds before retry...');
                        sleep(10);

                        continue;
                    }

                    $this->error("❌ Health check failed after {$retries} attempts - HTTP {$response->status()}");

                    return $this->handleHealthCheckFailure();
                }

            } catch (\Exception $e) {
                $this->warn("⚠️  Health check attempt {$attempt} failed with exception: ".$e->getMessage());

                if ($attempt < $retries) {
                    $this->info('Waiting 10 seconds before retry...');
                    sleep(10);

                    continue;
                }

                $this->error("❌ Health check failed after {$retries} attempts due to exception: ".$e->getMessage());

                return $this->handleHealthCheckFailure();
            }
        }

        return 1;
    }

    private function handleHealthCheckFailure(): int
    {
        $this->error('Health check failed - putting site back in maintenance mode for investigation');

        try {
            $this->call('down', [
                '--retry' => 300, // 5 minutes
            ]);

            $this->info('Site has been put in maintenance mode');

        } catch (\Exception $e) {
            $this->error('Failed to put site in maintenance mode: '.$e->getMessage());
        }

        return 1;
    }
}
