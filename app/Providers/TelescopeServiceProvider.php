<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Whether register() has already run once in this process. Telescope::$filterUsing and
     * $hiddenRequestParameters (vendor) are process-lifetime static arrays with no public reset
     * method, and register() re-runs on every application rebuild (every test, since Pest
     * rebuilds the app per test) regardless of TELESCOPE_ENABLED. Without this guard they
     * accumulate one closure per boot forever, each pinning that boot's Application instance
     * alive via a captured $this->app -- the same leak class fixed in ActivityLogServiceProvider
     * and AppServiceProvider. Harmless in production, where a fresh process boots once per
     * request and this would only ever run once anyway.
     */
    private static bool $registered = false;

    /**
     * Register any application services.
     *
     * @return void
     */
    #[\Override]
    public function register()
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry) {
            if (! $this->app->environment('production')) {
                return true;
            }

            // check if request is 404, if yes, return true
            if ($entry->type === EntryType::REQUEST &&
                isset($entry->content['response_status']) &&
                $entry->content['response_status'] === 404) {
                return true;
            }

            return $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag() ||
                $entry->isSlowQuery() ||
                $entry->isLog() ||
                $entry->type === EntryType::MAIL;
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     *
     * @return void
     */
    protected function hideSensitiveRequestDetails()
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     *
     * @return void
     */
    #[\Override]
    protected function gate()
    {
        Gate::define('viewTelescope', fn (User $user) => $user->isSuperAdmin());
    }
}
