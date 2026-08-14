<?php

use App\Http\Kernel;
use Laravel\Head\Inertia\ShareHead;

/**
 * Regression guard for a real bootstrap-ordering bug: laravel/head's own Inertia integration
 * registers Laravel\Head\Inertia\ShareHead via Container::afterResolving(Kernel::class, ...),
 * which only fires for *future* resolutions of that binding. This app's bootstrap/app.php uses
 * the classic App\Http\Kernel style, where public/index.php resolves the Http Kernel (via
 * $app->make()) before any service provider — including HeadServiceProvider — has registered,
 * so the afterResolving callback queues against a binding that never resolves again and the
 * middleware silently never runs on a real request. (Laravel's testing HTTP client and `artisan
 * tinker` both bootstrap in an order where the callback happens to fire, so this only reproduces
 * against an actual served request — feature tests can't catch it, hence this explicit check.)
 *
 * ShareHead is therefore registered directly in App\Http\Kernel::$middleware instead of relying
 * on the package's own registration. This test guards that explicit entry against being "cleaned
 * up" by someone assuming the package handles it — see PublicController::applyPageHead().
 */
test('ShareHead middleware is explicitly registered on the global middleware stack', function (): void {
    // A booted app instance won't do here: Laravel's testing bootstrap (and `artisan tinker`)
    // happens to register service providers before the Http Kernel is ever resolved, so
    // laravel/head's own (broken, for this app) registration mechanism succeeds in that context
    // regardless of whether the explicit entry below exists — the bug only reproduces via the
    // real public/index.php request path. Reading the class's declared default value instead
    // checks the one thing that actually matters: that the source still has the fix.
    $property = new ReflectionProperty(Kernel::class, 'middleware');
    $middleware = $property->getDefaultValue();

    expect($middleware)->toContain(ShareHead::class);
});
