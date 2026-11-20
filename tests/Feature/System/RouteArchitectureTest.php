<?php

/**
 * Route Architecture Tests
 *
 * These tests ensure routes are organized correctly and follow conventions.
 * Note: These are feature tests because they need the Laravel application context.
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses()->group('routes');

const UNSCOPED_NESTED_ROUTES = [
    'agendaItems.goals.destroy' => 'Unlinks a goal step by agenda item ID under the goal’s update policy.',
    'agendaItems.problems.destroy' => 'Unlinks a many-to-many relation after authorizing the agenda item and checking the link.',
    'goals.problems.unlink' => 'Detaches a many-to-many link under the goal’s update policy.',
    'meetings.institutions.detach' => 'The policy evaluates both route models and access to the meeting after removing the link.',
    'responsibilities.users.destroy' => 'Super admins may remove direct role assignments after the controller verifies the membership.',
];

/*
|--------------------------------------------------------------------------
| API Route Tests
|--------------------------------------------------------------------------
*/

it('has API v1 routes registered', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes());

    $apiRoutes = $routes->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1'));

    expect($apiRoutes)->not->toBeEmpty();
});

it('has public API routes', function (): void {
    expect(Route::has('api.v1.types.index'))->toBeFalse()
        ->and(Route::has('api.v1.documents.index'))->toBeTrue()
        ->and(Route::has('api.v1.typesense.config'))->toBeTrue();
});

it('has admin API routes', function (): void {
    expect(Route::has('api.v1.admin.tasks.index'))->toBeTrue()
        ->and(Route::has('api.v1.admin.files.index'))->toBeTrue()
        ->and(Route::has('api.v1.admin.files.allowedTypes'))->toBeTrue()
        ->and(Route::has('api.v1.admin.tutorials.progress'))->toBeTrue();
});

it('requires authentication for admin API routes', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes());

    $adminApiRoutes = $routes->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/admin'));

    foreach ($adminApiRoutes as $route) {
        $middleware = $route->middleware();
        expect($middleware)->toContain('auth');
    }
});

it('does not require authentication for public API routes', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes());

    $publicApiRoutes = $routes->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1')
        && ! str_starts_with($route->uri(), 'api/v1/admin'));

    foreach ($publicApiRoutes as $route) {
        $middleware = $route->middleware();
        expect($middleware)->not->toContain('auth');
    }
});

/*
|--------------------------------------------------------------------------
| Route Naming Convention Tests
|--------------------------------------------------------------------------
*/

it('follows API route naming convention', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes());

    $apiRoutes = $routes->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1') && $route->getName());

    foreach ($apiRoutes as $route) {
        $name = $route->getName();

        // API routes should start with 'api.'
        expect($name)->toStartWith('api.');
    }
});

it('includes admin in admin API route names', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes());

    $adminApiRoutes = $routes->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/admin') && $route->getName());

    foreach ($adminApiRoutes as $route) {
        $name = $route->getName();

        // Admin API routes should include 'admin' in name
        expect($name)->toContain('admin');
    }
});

it('points every controller route at a method that exists', function (): void {
    $missing = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getActionName())
        ->filter(fn (string $action) => str_starts_with($action, 'App\\') && str_contains($action, '@'))
        ->reject(function (string $action): bool {
            [$class, $method] = explode('@', $action);

            return method_exists($class, $method);
        })
        ->values()
        ->all();

    expect($missing)->toBeEmpty(implode("\n", $missing));
});

it('scopes nested admin model bindings', function (): void {
    $unscoped = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $action = $route->getActionName();

        if (! Str::startsWith($action, ['App\Http\Controllers\Admin\\', 'App\Http\Controllers\Api\Admin\\'])
            || $route->enforcesScopedBindings()) {
            continue;
        }

        $name = $route->getName() ?? $route->uri();
        $parameters = $route->parameterNames();

        if (array_key_exists($name, UNSCOPED_NESTED_ROUTES) || count($parameters) < 2) {
            continue;
        }

        $bindings = array_filter(
            $route->signatureParameters(Model::class),
            fn (ReflectionParameter $parameter): bool => in_array($parameter->getName(), $parameters, true)
                || in_array(Str::snake($parameter->getName()), $parameters, true),
        );

        if (count($bindings) >= 2) {
            $unscoped[] = "{$name} -> {$action}";
        }
    }

    expect($unscoped)->toBeEmpty(implode("\n", $unscoped));
});
