<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Uri;

#[Description('Perform health check after deployment')]
#[Signature('deployment:health-check 
                            {--url= : The URL to check (defaults to APP_URL/up)}
                            {--timeout=30 : Request timeout in seconds}
                            {--retries=3 : Number of retry attempts}')]
class DeploymentHealthCheck extends Command
{
    public function handle(): int
    {
        $url = $this->option('url') ?: (string) Uri::of((string) config('app.url'))->withPath('/up');
        $timeout = (int) $this->option('timeout');
        $retries = (int) $this->option('retries');

        $this->info("Performing health check on: {$url}");

        // Wait a moment for the site to fully come online
        $this->info('Waiting 5 seconds for site to initialize...');
        Sleep::for(5)->seconds();

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
                        Sleep::for(10)->seconds();

                        continue;
                    }

                    $this->error("❌ Health check failed after {$retries} attempts - HTTP {$response->status()}");

                    return $this->handleHealthCheckFailure();
                }

            } catch (\Exception $e) {
                $this->warn("⚠️  Health check attempt {$attempt} failed with exception: ".$e->getMessage());

                if ($attempt < $retries) {
                    $this->info('Waiting 10 seconds before retry...');
                    Sleep::for(10)->seconds();

                    continue;
                }

                $this->error("❌ Health check failed after {$retries} attempts due to exception: ".$e->getMessage());

                return $this->handleHealthCheckFailure();
            }
        }

        return 1;
    }

    /**
     * Fail loudly, but leave the site up.
     *
     * This used to call `down --retry=300`. Combined with the `health` step being non-critical, that
     * was the worst of both worlds: deployment:run still returned 0, so the workflow went green while
     * the site sat in maintenance mode with nobody alerted. A failed check is also often a transient
     * blip, and downing the site turns that into a guaranteed outage.
     *
     * The step is critical now, so returning non-zero is enough to make the deploy go red. Taking the
     * site down deliberately stays a human decision (`artisan down` over SSH).
     */
    private function handleHealthCheckFailure(): int
    {
        $this->error('❌ Health check failed. The site has been left ONLINE — check it now.');
        $this->warn('   If it is genuinely broken, take it down by hand: php artisan down --retry=300');

        return 1;
    }
}
