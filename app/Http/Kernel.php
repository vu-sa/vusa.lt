<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\BlockRobotsOnStagingDomains;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\ExtendPWASession;
use App\Http\Middleware\GetNavigationForPublic;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RewriteUploadsUrl;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\StagingBasicAuth;
use App\Http\Middleware\StagingEnvironmentWarnings;
use App\Http\Middleware\StagingReadOnlyMode;
use App\Http\Middleware\TenantPermission;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\UpdateLastAction;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, string>
     */
    protected $middleware = [
        StagingBasicAuth::class,
        BlockRobotsOnStagingDomains::class,
        HandleCors::class,
        // \App\Http\Middleware\TrustHosts::class,
        TrustProxies::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var string[]
     */
    protected $middlewarePriority = [
        SetLocale::class,
        // getting navigation
        // getting padalinys links
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, string>>
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            ExtendPWASession::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            StagingReadOnlyMode::class,
            RewriteUploadsUrl::class,
            SetLocale::class,
            HandleInertiaRequests::class,
            StagingEnvironmentWarnings::class,
            UpdateLastAction::class,
        ],

        'api' => [
            'throttle:api',
            SubstituteBindings::class,
            StagingReadOnlyMode::class,
        ],

        'main' => [
            GetNavigationForPublic::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, string>
     */
    protected $middlewareAliases = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'locale' => SetLocale::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        // 'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'tenant.permission' => TenantPermission::class,
    ];
}
