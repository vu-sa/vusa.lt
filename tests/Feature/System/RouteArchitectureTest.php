<?php

/**
 * Route Architecture Tests
 *
 * These tests ensure routes are organized correctly and follow conventions.
 * Note: These are feature tests because they need the Laravel application context.
 */

use Illuminate\Support\Facades\Route;

uses()->group('routes');

/*
|--------------------------------------------------------------------------
| API Route Tests
|--------------------------------------------------------------------------
*/

it('has API v1 routes registered', function () {
    $routes = collect(Route::getRoutes()->getRoutes());

    $apiRoutes = $routes->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1'));

    expect($apiRoutes)->not->toBeEmpty();
});

it('has public API routes', function () {
    expect(Route::has('api.v1.types.index'))->toBeTrue();
    expect(Route::has('api.v1.documents.index'))->toBeTrue();
    expect(Route::has('api.v1.typesense.config'))->toBeTrue();
});

it('has admin API routes', function () {
    expect(Route::has('api.v1.admin.tasks.indicator'))->toBeTrue();
    expect(Route::has('api.v1.admin.files.index'))->toBeTrue();
    expect(Route::has('api.v1.admin.files.allowedTypes'))->toBeTrue();
    expect(Route::has('api.v1.admin.fileables.files'))->toBeTrue();
    expect(Route::has('api.v1.admin.sharepoint.potentialFileables'))->toBeTrue();
    expect(Route::has('api.v1.admin.tutorials.progress'))->toBeTrue();
});

it('requires authentication for admin API routes', function () {
    $routes = collect(Route::getRoutes()->getRoutes());

    $adminApiRoutes = $routes->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/admin'));

    foreach ($adminApiRoutes as $route) {
        $middleware = $route->middleware();
        expect($middleware)->toContain('auth');
    }
});

it('does not require authentication for public API routes', function () {
    $routes = collect(Route::getRoutes()->getRoutes());

    $publicApiRoutes = $routes->filter(function ($route) {
        return str_starts_with($route->uri(), 'api/v1')
            && ! str_starts_with($route->uri(), 'api/v1/admin');
    });

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

it('follows API route naming convention', function () {
    $routes = collect(Route::getRoutes()->getRoutes());

    $apiRoutes = $routes->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1') && $route->getName());

    foreach ($apiRoutes as $route) {
        $name = $route->getName();

        // API routes should start with 'api.'
        expect($name)->toStartWith('api.');
    }
});

it('includes admin in admin API route names', function () {
    $routes = collect(Route::getRoutes()->getRoutes());

    $adminApiRoutes = $routes->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/admin') && $route->getName());

    foreach ($adminApiRoutes as $route) {
        $name = $route->getName();

        // Admin API routes should include 'admin' in name
        expect($name)->toContain('admin');
    }
});
