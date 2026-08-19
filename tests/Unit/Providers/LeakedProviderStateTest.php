<?php

use App\Providers\TelescopeServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\Facades\Facade;
use Laravel\Telescope\Telescope;
use Spatie\Activitylog\Actions\LogActivityAction;

/**
 * Regression guard for a real, measured bug: three providers (ActivityLogServiceProvider,
 * AppServiceProvider, TelescopeServiceProvider) each registered a closure with a vendor static
 * registry from boot()/register() — none of which are container-scoped, and none of which were
 * ever cleared. Every Pest test rebuilds the Application from scratch (see
 * tests/CreatesApplication.php), re-running every provider's boot()/register(), so each of these
 * registries grew by one closure per test, forever, across the whole suite. Confirmed to cause a
 * severe process-wide slowdown: tests unrelated to activity logging measured 12-58x slower once
 * ~800+ tests had run in the same PHP process (the same file/tests ran fast standalone, slow
 * embedded late in a full sequential run). Each closure also captured $this->app, so the leak
 * pinned every past Application instance alive for the rest of the process.
 *
 * This reproduces that exact scenario — two full application boots in one process — and asserts
 * the registries stay bounded.
 */
function bootFreshApplicationForLeakTest(): void
{
    $app = require base_path('bootstrap/app.php');
    $app->make(Kernel::class)->bootstrap();

    // Application::__construct() already called Container::setInstance($app) internally;
    // this keeps facades consistent with it for the duration of this call.
    Facade::setFacadeApplication($app);
    Facade::clearResolvedInstances();
}

function countStaticCallbacks(string $class, string $property): int
{
    $reflection = new ReflectionProperty($class, $property);

    return count($reflection->getValue());
}

test('activity log, trim-strings, and telescope registrations do not accumulate across application rebuilds', function (): void {
    bootFreshApplicationForLeakTest();

    $afterFirstBoot = [
        'activitylog' => countStaticCallbacks(LogActivityAction::class, 'beforeLoggingCallbacks'),
        'trimStrings' => countStaticCallbacks(TrimStrings::class, 'skipCallbacks'),
        'telescope' => countStaticCallbacks(Telescope::class, 'filterUsing'),
    ];

    bootFreshApplicationForLeakTest();

    $afterSecondBoot = [
        'activitylog' => countStaticCallbacks(LogActivityAction::class, 'beforeLoggingCallbacks'),
        'trimStrings' => countStaticCallbacks(TrimStrings::class, 'skipCallbacks'),
        'telescope' => countStaticCallbacks(Telescope::class, 'filterUsing'),
    ];

    expect($afterSecondBoot)->toBe($afterFirstBoot)
        ->and($afterFirstBoot['activitylog'])->toBe(1)
        ->and($afterFirstBoot['trimStrings'])->toBe(1)
        ->and($afterFirstBoot['telescope'])->toBe(1);

    // Restore the real test application so the rest of this process is unaffected.
    Container::setInstance(app());
    Facade::setFacadeApplication(app());
    Facade::clearResolvedInstances();
});

test('TelescopeServiceProvider only registers once per process', function (): void {
    bootFreshApplicationForLeakTest();
    bootFreshApplicationForLeakTest();

    $reflection = new ReflectionProperty(TelescopeServiceProvider::class, 'registered');

    expect($reflection->getValue())->toBeTrue();

    Container::setInstance(app());
    Facade::setFacadeApplication(app());
    Facade::clearResolvedInstances();
});
