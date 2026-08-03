<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Description('Warm up the cache for public routes')]
#[Signature('cache:warm')]
class WarmCache extends Command
{
    public function handle()
    {
        $this->info('Starting cache warming...');

        $tenants = Tenant::all();
        $locales = ['lt', 'en'];
        $baseUrl = config('app.url');

        foreach ($tenants as $tenant) {
            foreach ($locales as $locale) {
                $this->warmTenantPages($tenant, $locale, $baseUrl);
            }
        }

        $this->info('Cache warming completed!');
    }

    private function warmTenantPages(Tenant $tenant, string $locale, string $baseUrl)
    {
        $subdomain = $tenant->alias === 'vusa' ? 'www' : $tenant->alias;
        $url = str_replace('www', $subdomain, $baseUrl);

        // Warm homepage
        $homeUrl = "{$url}/{$locale}";
        $this->info("Warming: {$homeUrl}");

        try {
            Http::timeout(10)->get($homeUrl);
        } catch (\Exception $e) {
            $this->warn("Failed to warm {$homeUrl}: ".$e->getMessage());
        }

        // Add a small delay to prevent overwhelming the server
        usleep(100000); // 100ms
    }
}
