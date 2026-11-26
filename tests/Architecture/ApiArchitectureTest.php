<?php

/**
 * API Architecture Tests
 *
 * These tests enforce the API architecture conventions established for the application.
 * They ensure controllers follow the correct patterns and use appropriate base classes.
 *
 * @see CLAUDE.md for API architecture documentation
 */

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/*
|--------------------------------------------------------------------------
| API Controller Structure Tests
|--------------------------------------------------------------------------
*/

arch('API controllers extend ApiController')
    ->expect('App\Http\Controllers\Api')
    ->classes()
    ->toExtend(ApiController::class)
    ->ignoring(ApiController::class);

arch('API controllers use consistent naming')
    ->expect('App\Http\Controllers\Api')
    ->classes()
    ->toHaveSuffix('Controller');

arch('Admin API directory contains only classes')
    ->expect('App\Http\Controllers\Api\Admin')
    ->toBeClasses();

/*
|--------------------------------------------------------------------------
| Controller Separation Tests
|--------------------------------------------------------------------------
*/

arch('Admin controllers do not extend ApiController')
    ->expect('App\Http\Controllers\Admin')
    ->classes()
    ->not->toExtend(ApiController::class);

arch('Public controllers do not extend ApiController')
    ->expect('App\Http\Controllers\Public')
    ->classes()
    ->not->toExtend(ApiController::class);

/*
|--------------------------------------------------------------------------
| Dependency Tests
|--------------------------------------------------------------------------
*/

arch('API controllers do not use Inertia')
    ->expect('App\Http\Controllers\Api')
    ->not->toUse([Inertia::class, Response::class]);

arch('API controllers return JsonResponse')
    ->expect('App\Http\Controllers\Api')
    ->toUse([JsonResponse::class]);

/*
|--------------------------------------------------------------------------
| Naming Convention Tests
|--------------------------------------------------------------------------
*/

arch('Admin API controllers use ApiController suffix')
    ->expect('App\Http\Controllers\Api\Admin')
    ->classes()
    ->toHaveSuffix('ApiController');
