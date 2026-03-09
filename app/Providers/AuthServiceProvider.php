<?php

namespace App\Providers;

use App\Models\User;
use App\Settings\SettingsSettings;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // explicit policies that are not inferred from model names
        \App\Models\InstitutionCheckIn::class => \App\Policies\InstitutionCheckInPolicy::class,
        \App\Models\FileableFile::class => \App\Policies\FileableFilePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function (User $user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Define gate for settings management access
        Gate::define('manage-settings', function (User $user) {
            return app(SettingsSettings::class)->canUserManageSettings($user);
        });

        // Define gate for administration page access
        // User can access if they have viewAny permission on any administrative model
        // Excludes resource model since it's managed through the Reservations dashboard
        Gate::define('access-administration', function (User $user) {
            $labels = \App\Enums\ModelEnum::toLabels();

            // Remove special cases that don't grant admin access
            $excludedModels = [
                'reservationResource',
                'file',
                'resource', // Managed through Reservations dashboard
            ];

            foreach ($excludedModels as $excluded) {
                $key = array_search($excluded, $labels);
                if ($key !== false) {
                    unset($labels[$key]);
                }
            }

            foreach ($labels as $model) {
                if ($user->can('viewAny', 'App\\Models\\'.ucfirst($model))) {
                    return true;
                }
            }

            return false;
        });
    }
}
