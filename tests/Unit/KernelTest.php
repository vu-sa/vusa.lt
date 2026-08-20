<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Kernel as FoundationHttpKernel;
use Illuminate\Support\Facades\Facade;
use Laravel\Head\Inertia\ShareHead;

/**
 * Regression guard for a real bootstrap-ordering bug: laravel/head's own Inertia integration
 * registers Laravel\Head\Inertia\ShareHead via Container::afterResolving(Kernel::class, ...),
 * which only fires for *future* resolutions of that binding. On a real FPM request,
 * public/index.php resolves (and caches) the HTTP kernel via $app->handleRequest() *before*
 * any service provider — including laravel/head's — has registered, so the afterResolving
 * callback queues against a binding that never resolves again and the middleware silently
 * never runs. (Laravel's testing HTTP client and `artisan tinker` both bootstrap in an order
 * where the callback happens to fire, so feature tests can't catch it — see
 * PublicController::applyPageHead().)
 *
 * ShareHead is therefore registered explicitly in bootstrap/app.php's withMiddleware()
 * config instead of relying on the package's own registration. This test reproduces the
 * real FPM ordering — kernel resolved before providers — and guards the explicit entry
 * against being "cleaned up" by someone assuming the package handles it.
 */
test('ShareHead middleware is explicitly registered on the global middleware stack', function (): void {
    // Fresh application, built exactly like public/index.php does it: the kernel is made
    // BEFORE anything bootstraps providers. If the explicit withMiddleware() entry were
    // removed, nothing would add ShareHead at this point — proving the package's own
    // registration cannot cover the real request path.
    $app = require base_path('bootstrap/app.php');

    /** @var FoundationHttpKernel $kernel */
    $kernel = $app->make(Kernel::class);

    expect($kernel)->toBeInstanceOf(FoundationHttpKernel::class)
        ->and($kernel->hasMiddleware(ShareHead::class))->toBeTrue();

    // The fresh application replaced the container and facade targets; restore the test app.
    Container::setInstance(app());
    Facade::setFacadeApplication(app());
    Facade::clearResolvedInstances();
});
