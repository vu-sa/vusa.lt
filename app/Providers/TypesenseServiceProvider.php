<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Typesense\Client;

class TypesenseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        // Register the Typesense client as a singleton
        $this->app->singleton(Client::class, fn ($app) => new Client(config('scout.typesense.client-settings')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Service provider boots automatically
    }
}
