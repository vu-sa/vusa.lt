<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Models\Agenda' => 'App\Policies\AgendaPolicy',
        'App\Models\Banner' => 'App\Policies\BannerPolicy',
        'App\Models\Contact' => 'App\Policies\ContactPolicy',
        'App\Models\MainPage' => 'App\Policies\MainPagePolicy',
        'App\Models\Navigation' => 'App\Policies\NavigationPolicy',
        'App\Models\Padalinys' => 'App\Policies\PadaliniaiPolicy',
        'App\Models\Page' => 'App\Policies\PagesPolicy',
        'App\Models\Saziningai' => 'App\Policies\SaziningaiPolicy',
        'App\Models\User' => 'App\Policies\UserPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}