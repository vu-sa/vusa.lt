<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Typesense\Client;

class TypesenseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the Typesense client as a singleton
        $this->app->singleton(Client::class, function ($app) {
            return new Client(config('scout.typesense.client-settings'));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {}
}