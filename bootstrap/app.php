<?php

use App\Http\Middleware\BlockRobotsOnStagingDomains;
use App\Http\Middleware\ExtendPWASession;
use App\Http\Middleware\GetNavigationForPublic;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RewriteUploadsUrl;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\StagingBasicAuth;
use App\Http\Middleware\StagingEnvironmentWarnings;
use App\Http\Middleware\StagingReadOnlyMode;
use App\Http\Middleware\TenantPermission;
use App\Http\Middleware\UpdateLastAction;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Head\Inertia\ShareHead;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Registered by the framework before the route files below load, so web.php's
        // catch-all {permalink} route (where('permalink', '.*')) cannot swallow it, and
        // excepted from PreventRequestsDuringMaintenance so health checks survive `down`.
        health: '/up',
        then: function (): void {
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
                ->namespace('App\\Http\\Controllers')
                ->group(base_path('routes/web.php'));
        },
        commands: __DIR__.'/../routes/console.php',
    )
    // Explicit $listen/$subscribe in App\Providers\EventServiceProvider is the source of
    // truth for listeners; automatic discovery of app/Listeners would double-register them.
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend([
            StagingBasicAuth::class,
            BlockRobotsOnStagingDomains::class,
        ]);

        // Registered explicitly rather than relying on laravel/head's own registration:
        // that hooks afterResolving(Kernel::class, ...), which never fires on real requests —
        // public/index.php resolves (and caches) the HTTP kernel before any service provider
        // registers, so the package's callback queues against a binding that never resolves
        // again. It only happens to work in tests/tinker, where the kernel is resolved after
        // providers. See tests/Unit/KernelTest.php.
        $middleware->append([ShareHead::class]);

        // Configuration previously carried by app/Http/Middleware subclasses:
        // EncryptCookies (pwa_mode cookie must stay readable client-side), TrustProxies
        // (Sail's docker networks), and the guest/auth redirect targets.
        $middleware->encryptCookies(except: ['pwa_mode']);
        $middleware->trustProxies(at: ['172.16.0.0/12']);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        // ExtendPWASession must run after EncryptCookies (it reads the decrypted pwa_mode
        // cookie) but before StartSession (it adjusts session config before sessions start),
        // so the web group is defined explicitly instead of extending the framework default.
        $middleware->group('web', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            ExtendPWASession::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            StagingReadOnlyMode::class,
            RewriteUploadsUrl::class,
            SetLocale::class,
            HandleInertiaRequests::class,
            StagingEnvironmentWarnings::class,
            UpdateLastAction::class,
        ]);

        $middleware->group('api', [
            'throttle:api',
            SubstituteBindings::class,
            StagingReadOnlyMode::class,
        ]);

        $middleware->group('main', [
            GetNavigationForPublic::class,
        ]);

        // getting navigation
        // getting padalinys links
        $middleware->priority([SetLocale::class]);

        $middleware->alias([
            'locale' => SetLocale::class,
            'tenant.permission' => TenantPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request): ?Response {
            // Maintenance mode gets the dedicated maintenance view instead of the generic
            // 503 page. PreventRequestsDuringMaintenance throws a plain HttpException with
            // no maintenance-specific type, so the only way to tell it apart from a genuine
            // 503 is to ask the application whether it is currently down.
            if ($e instanceof HttpException && $e->getStatusCode() === 503
                && app()->isDownForMaintenance() && ! $request->expectsJson()) {
                return response()->view('errors.maintenance', ['exception' => $e], 503, $e->getHeaders());
            }

            // Handle 403 errors with redirect and flash message for Inertia requests.
            // Direct visits get the full 403 error page.
            if ($e instanceof HttpException && $e->getStatusCode() === 403
                && $request->header('X-Inertia')) {
                return back()->with([
                    'error' => __($e->getMessage() ?: 'This action is unauthorized.'),
                ]);
            }

            return null;
        });
    })->create();
