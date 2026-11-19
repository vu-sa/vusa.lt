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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

pest()->use(RefreshDatabase::class);

/**
 * Routes that are genuinely self-scoped: they only ever read or write rows belonging to the
 * acting user, resolved from `Auth::id()` / `$request->user()` rather than from request input.
 * There is no other subject to authorize against.
 */
const SELF_SCOPED_ROUTE_NAMES = [
    'logout',
    'logout.microsoft',
    'profile.update',
    'profile.updatePassword',
    'profile.updateNotificationPreferences',
    'profile.muteNotifications',
    'profile.resetNotificationPreferences',
    'profile.sendTestNotificationEmail',
    'notifications.markAsRead',
    'notifications.mark-as-read.all',
    'notifications.destroy',
    'notifications.destroy-all',
    'api.v1.admin.pushSubscriptions.store',
    'api.v1.admin.pushSubscriptions.destroy',
    'api.v1.admin.pushSubscriptions.destroyById',
    'api.v1.admin.pushSubscriptions.test',
    'api.v1.admin.tutorials.complete',
    'api.v1.admin.tutorials.reset',
    'api.v1.admin.tutorials.resetAll',
    'api.v1.admin.user-preferences.update',
    'api.v1.admin.user-preferences.trackRecentPage',
    'api.v1.admin.search.refreshKey',
    // Removing your own follow only touches rows keyed on the acting user.
    'api.v1.admin.institutions.unfollow',
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
 * Whether the controller method carries `#[Authorize]`, authorizes in its body, delegates to a
 * helper on the controller that authorizes, or type-hints a Form Request whose authorize() is
 * more than a bare `return true`.
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

    if (hasAuthorizeAttribute($reflection)) {
        return true;
    }

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

function hasAuthorizeAttribute(ReflectionMethod $reflection): bool
{
    return $reflection->getAttributes(Authorize::class) !== []
        || $reflection->getDeclaringClass()->getAttributes(Authorize::class) !== [];
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

    expect($unprotected)->toBeEmpty(implode("\n", $unprotected));
});

/**
 * @return array<int, class-string>
 */
function appClassesIn(string $directory): array
{
    $namespace = 'App\\'.str_replace('/', '\\', $directory).'\\';

    return collect(Finder::create()->files()->in(app_path($directory))->name('*.php'))
        ->map(fn (SplFileInfo $file): string => $namespace.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()))
        ->filter(fn (string $class): bool => class_exists($class))
        ->values()
        ->all();
}

/**
 * A method body's statements, comments and outer braces stripped.
 *
 * @return array<int, string>
 */
function methodStatements(ReflectionMethod $reflection): array
{
    $body = (string) preg_replace(['/\/\*.*?\*\//s', '/\/\/[^\n]*/'], '', methodBody($reflection));
    $body = Str::of($body)->after('{')->beforeLast('}')->squish()->toString();

    return array_values(array_filter(array_map('trim', explode(';', $body))));
}

/**
 * A leading `$this->authorize()` on a class or a route-bound model belongs in `#[Authorize]`,
 * which runs before validation and sits where a reader looks first.
 */
test('plain controller checks are #[Authorize] attributes', function (): void {
    $plain = [];

    foreach (appClassesIn('Http/Controllers') as $class) {
        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class || $method->isStatic()) {
                continue;
            }

            $boundModels = collect($method->getParameters())
                ->filter(fn (ReflectionParameter $parameter): bool => $parameter->getType() instanceof ReflectionNamedType
                    && is_subclass_of($parameter->getType()->getName(), Model::class))
                ->map(fn (ReflectionParameter $parameter): string => $parameter->getName())
                ->all();

            foreach (methodStatements($method) as $statement) {
                if (! preg_match('/^\$this->authorize\(\s*[\'"]\w+[\'"]\s*,\s*(.+)\)$/', $statement, $match)) {
                    break;
                }

                $subjects = preg_split('/\s*,\s*/', trim($match[1], '[] '));
                $isPlain = collect($subjects)->every(fn (string $subject): bool => str_ends_with($subject, '::class')
                    || in_array(ltrim($subject, '$'), $boundModels, true));

                if ($isPlain) {
                    $plain[] = "{$class}@{$method->getName()}: {$statement}";
                }
            }
        }
    }

    expect($plain)->toBeEmpty(implode("\n", $plain));
});

/**
 * A Form Request's authorize() holds only what an attribute cannot express (request input,
 * several records). A bare `return true` is the framework default, so it is deleted.
 */
test('Form Requests hold no plain checks', function (): void {
    $plain = [];
    $subject = '([\\\\\w]+::class|\$this->route\([\'"]\w+[\'"]\)|\$this->\w+)';

    foreach (appClassesIn('Http/Requests') as $class) {
        if (! is_subclass_of($class, FormRequest::class) || ! method_exists($class, 'authorize')) {
            continue;
        }

        $method = new ReflectionMethod($class, 'authorize');

        if ($method->getDeclaringClass()->getName() !== $class) {
            continue;
        }

        $statements = methodStatements($method);

        if ($statements === ['return true']
            || (count($statements) === 1 && preg_match('/^return \$this->user\(\)\??->can\([\'"]\w+[\'"], '.$subject.'\)( \?\? false)?$/', $statements[0]))) {
            $plain[] = "{$class}: {$statements[0]}";
        }
    }

    expect($plain)->toBeEmpty(implode("\n", $plain));
});
