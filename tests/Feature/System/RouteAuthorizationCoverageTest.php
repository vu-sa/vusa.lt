<?php

/**
 * Authorization Architecture Tests
 *
 * Every mutating admin route must authorize. The `auth` middleware only proves *who* the user
 * is, not *what* they may touch, and `routes/admin.php` applies nothing else — so a controller
 * method reachable by POST/PUT/PATCH/DELETE with no authorization call and no gating Form
 * Request is an open door.
 *
 * This test walks the real route table rather than a hand-kept list, so a newly registered
 * route is covered the moment it exists. Exemptions are enumerated below and each one has to
 * be justified in review.
 *
 * It lives under Feature rather than Architecture because it walks the booted route table,
 * and tests/Architecture runs without an application instance.
 *
 * @see AGENTS.md — "Every mutating route authorizes"
 */

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

pest()->use(RefreshDatabase::class);

/**
 * Routes that are genuinely self-scoped: they only ever read or write rows belonging to the
 * acting user, resolved from `Auth::id()` / `$request->user()` rather than from request input.
 * There is no other subject to authorize against.
 */
const SELF_SCOPED_ROUTE_NAMES = [
    'logout',
    'profile.update',
    'profile.updatePassword',
    'profile.updateNotificationPreferences',
    'profile.sendTestNotificationEmail',
    'notifications.markAsRead',
    'notifications.mark-as-read.all',
    'notifications.destroy',
    'notifications.destroy-all',
    'push-subscription.store',
    'push-subscription.destroy',
    'push-subscription.destroyById',
    'push-subscription.test',
    'api.v1.admin.tutorials.complete',
    'api.v1.admin.tutorials.reset',
    'api.v1.admin.tutorials.resetAll',
    'api.v1.admin.user-preferences.update',
    'api.v1.admin.user-preferences.trackRecentPage',
    'api.v1.admin.search.refreshKey',
    'api.v1.admin.subscriptions.reset',
    // Removing your own follow/mute — the row is keyed on the acting user. (follow/mute, which
    // create the row against a named institution, do authorize.)
    'api.v1.admin.institutions.unfollow',
    'api.v1.admin.institutions.unmute',
    // The login form itself; guarded by the `guest` middleware, not by a policy.
    'login',
    // Impersonation guards on env + super-admin inside the controller, not via a policy.
    'api.v1.admin.impersonate.start',
    'api.v1.admin.impersonate.stop',
];

/**
 * Tokens that count as an authorization check in a controller method body.
 */
const AUTHORIZATION_TOKENS = [
    '$this->authorize(',
    '$this->handleAuthorization(',
    '$this->authorizeApi(',
    'abort_if(',
    'abort_unless(',
    'Gate::',
    '->can(',
    '->cannot(',
];

/**
 * @return array<int, array{name: string, action: string}>
 */
function mutatingAdminRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => (bool) array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']))
        ->filter(function (RoutingRoute $route): bool {
            $action = $route->getActionName();

            return str_starts_with($action, 'App\Http\Controllers\Admin\\')
                || str_starts_with($action, 'App\Http\Controllers\Api\Admin\\');
        })
        // Unnamed routes fall back to their URI so they can still be exempted by hand.
        ->map(fn (RoutingRoute $route) => ['name' => $route->getName() ?? $route->uri(), 'action' => $route->getActionName()])
        ->reject(fn (array $route) => in_array($route['name'], SELF_SCOPED_ROUTE_NAMES, true))
        ->values()
        ->all();
}

/**
 * Whether the controller method authorizes directly, delegates to a helper on the controller
 * that authorizes (e.g. HandlesSoftDeletes::restoreModel), or type-hints a Form Request whose
 * authorize() is more than a bare `return true`.
 */
function routeActionAuthorizes(string $action): bool
{
    if (! str_contains($action, '@')) {
        return true; // Invokable controllers are checked by hand.
    }

    [$class, $method] = explode('@', $action);

    if (! class_exists($class) || ! method_exists($class, $method)) {
        return true; // Missing methods are a routing bug, not an authorization one.
    }

    $reflection = new ReflectionMethod($class, $method);
    $body = methodBody($reflection);

    if (bodyAuthorizes($body)) {
        return true;
    }

    // One level of delegation: `return $this->restoreModel($model);` and friends.
    if (preg_match_all('/\$this->([a-zA-Z_][a-zA-Z0-9_]*)\(/', $body, $matches)) {
        foreach (array_unique($matches[1]) as $called) {
            if ($called === $method || ! method_exists($class, $called)) {
                continue;
            }

            if (bodyAuthorizes(methodBody(new ReflectionMethod($class, $called)))) {
                return true;
            }
        }
    }

    foreach ($reflection->getParameters() as $parameter) {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            continue;
        }

        if (formRequestGates($type->getName())) {
            return true;
        }
    }

    return false;
}

function methodBody(ReflectionMethod $reflection): string
{
    $file = file($reflection->getFileName());

    return implode('', array_slice(
        $file,
        $reflection->getStartLine() - 1,
        $reflection->getEndLine() - $reflection->getStartLine() + 1
    ));
}

function bodyAuthorizes(string $body): bool
{
    return array_any(AUTHORIZATION_TOKENS, fn ($token) => str_contains($body, $token));
}

/**
 * A Form Request gates the route only if it declares authorize() with a real body.
 */
function formRequestGates(string $class): bool
{
    if (! is_subclass_of($class, FormRequest::class)) {
        return false;
    }

    if (! method_exists($class, 'authorize')) {
        return false;
    }

    // A body that does nothing but `return true;` is not a gate. An authorize() that returns
    // true on one branch and checks an ability on another (the usual "let validation report a
    // missing parent" shape) is.
    $statements = preg_replace(
        ['/\/\*.*?\*\//s', '/\/\/[^\n]*/', '/^\s*(public|protected)?\s*function authorize[^\n]*\n/m', '/^\s*[{}]\s*$/m', '/\s+/'],
        ['', '', '', '', ' '],
        methodBody(new ReflectionMethod($class, 'authorize'))
    );

    return trim((string) $statements) !== 'return true;';
}

test('every mutating admin route authorizes', function (): void {
    $unprotected = collect(mutatingAdminRoutes())
        ->reject(fn (array $route) => routeActionAuthorizes($route['action']))
        ->map(fn (array $route) => "{$route['name']} -> {$route['action']}")
        ->values()
        ->all();

    expect($unprotected)->toBeEmpty();
});
