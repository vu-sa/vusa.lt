<?php

/**
 * Controller Architecture Tests
 *
 * These tests enforce controller conventions across the application.
 * They ensure proper separation between Inertia and API controllers.
 */

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Controller;

/*
|--------------------------------------------------------------------------
| Base Controller Tests
|--------------------------------------------------------------------------
*/

arch('all controllers extend base Controller')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toExtend(Controller::class);

arch('AdminController provides Inertia helpers')
    ->expect(AdminController::class)
    ->toHaveMethods([
        'inertiaResponse',
        'redirectResponse',
        'redirectBack',
        'handleAuthorization',
    ]);

/*
|--------------------------------------------------------------------------
| Admin Controller Tests
|--------------------------------------------------------------------------
*/

// Note: Some admin controllers that only return JSON (PushSubscriptionController)
// may extend Controller directly. AuthController is also special.
// These are exceptions to the AdminController rule.
arch('admin controllers extend AdminController')
    ->expect('App\Http\Controllers\Admin')
    ->classes()
    ->toExtend(AdminController::class)
    ->ignoring([
        'App\Http\Controllers\Admin\PushSubscriptionController',
        'App\Http\Controllers\Admin\AuthController',
    ]);

arch('admin controllers are in Admin namespace')
    ->expect('App\Http\Controllers\Admin')
    ->toBeClasses();

/*
|--------------------------------------------------------------------------
| Public Controller Tests
|--------------------------------------------------------------------------
*/

arch('public controllers are in Public namespace')
    ->expect('App\Http\Controllers\Public')
    ->toBeClasses();

/*
|--------------------------------------------------------------------------
| No Mixing Patterns
|--------------------------------------------------------------------------
*/

arch('controllers do not use dd or dump')
    ->expect('App\Http\Controllers')
    ->not->toUse(['dd', 'dump', 'die']);
