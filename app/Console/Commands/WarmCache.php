<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WarmCache extends Command
{
    protected $signature = 'cache:warm';
    protected $description = 'Warm up the cache for public routes';

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
            $this->warn("Failed to warm {$homeUrl}: " . $e->getMessage());
        }
        
        // Add a small delay to prevent overwhelming the server
        usleep(100000); // 100ms
    }
}