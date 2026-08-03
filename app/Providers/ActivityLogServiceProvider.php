<?php

namespace App\Providers;

use App\Models\Activity;
use App\Services\ActivityRootResolver;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

/**
 * Wires up the activity log root roll-up (see App\Support\ActivityRoots and
 * App\Services\ActivityRootResolver).
 */
class ActivityLogServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        // Singleton so the per-request memo in ActivityRootResolver actually
        // pays off across multiple activities logged in one request.
        $this->app->singleton(ActivityRootResolver::class);
    }

    public function boot(): void
    {
        // The primary stamping point, not App\Models\Activity's `creating`
        // hook: beforeLogging() also fires for manual activity() calls and
        // survives config('activitylog.buffer.enabled'), whose flush() does a
        // bulk insert() that fires no Eloquent model events.
        // The callback signature is the base contract (matching beforeLogging's
        // declared type), but the instance is always our configured
        // activity_model (App\Models\Activity) in practice -- the instanceof
        // check is a defensive no-op unless that config is ever changed.
        LogActivityAction::beforeLogging(function (ActivityContract $activity): void {
            if ($activity instanceof Activity) {
                $this->app->make(ActivityRootResolver::class)->stamp($activity);
            }
        });
    }
}
