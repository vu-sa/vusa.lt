<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/mano';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->name('api.')
                ->namespace('App\\Http\\Controllers\\Api')
                ->group(base_path('routes/api.php'));

            Route::middleware(['web', 'auth'])
                ->namespace('App\\Http\\Controllers\\Admin')
                ->prefix('mano')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('summerCamps', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(100)->by($request->user()->id)
                : Limit::perMinute(15)->by($request->ip());
        });

        RateLimiter::for('formRegistrations', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(100)->by($request->user()->id)
                : Limit::perHour(5)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });
    }
}