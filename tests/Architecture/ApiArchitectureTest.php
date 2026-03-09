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
use App\Http\Controllers\Concerns\ApiResponses;

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

arch('Admin API controllers are in Admin namespace')
    ->expect('App\Http\Controllers\Api\Admin')
    ->toBeClasses()
    ->toExtend(ApiController::class);

arch('ApiController uses ApiResponses trait')
    ->expect(ApiController::class)
    ->toUseTrait(ApiResponses::class);

/*
|--------------------------------------------------------------------------
| Response Trait Tests
|--------------------------------------------------------------------------
*/

arch('ApiResponses trait exists and is a trait')
    ->expect(ApiResponses::class)
    ->toBeTrait();

arch('ApiResponses has required methods')
    ->expect(ApiResponses::class)
    ->toHaveMethods([
        'jsonSuccess',
        'jsonError',
        'jsonPaginated',
        'jsonNotFound',
        'jsonForbidden',
        'jsonUnauthorized',
        'jsonValidationError',
        'jsonCreated',
        'jsonNoContent',
    ]);

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
    ->not->toUse(['Inertia\Inertia', 'Inertia\Response']);

arch('API controllers return JsonResponse')
    ->expect('App\Http\Controllers\Api')
    ->toUse(['Illuminate\Http\JsonResponse']);

/*
|--------------------------------------------------------------------------
| Naming Convention Tests
|--------------------------------------------------------------------------
*/

arch('Admin API controllers use ApiController suffix')
    ->expect('App\Http\Controllers\Api\Admin')
    ->classes()
    ->toHaveSuffix('ApiController');
