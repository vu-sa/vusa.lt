<?php

/**
 * Tasks Architecture Tests
 *
 * These tests enforce conventions for the task system to ensure
 * proper structure, naming, and test coverage.
 *
 * @see app/Tasks/README.md
 */

use App\Tasks\Handlers\BaseTaskHandler;
use App\Tasks\Handlers\TaskHandler;

/*
|--------------------------------------------------------------------------
| Task Handlers Architecture
|--------------------------------------------------------------------------
|
| All task handlers must implement the TaskHandler interface and extend
| BaseTaskHandler. They must have proper suffix naming.
|
*/

arch('task handlers implement TaskHandler interface')
    ->expect('App\Tasks\Handlers')
    ->toImplement(TaskHandler::class)
    ->ignoring([
        BaseTaskHandler::class,
        TaskHandler::class,
    ]);

arch('task handlers extend BaseTaskHandler')
    ->expect('App\Tasks\Handlers')
    ->toExtend(BaseTaskHandler::class)
    ->ignoring([
        BaseTaskHandler::class,
        TaskHandler::class,
    ]);

arch('task handlers have Handler suffix')
    ->expect('App\Tasks\Handlers')
    ->toHaveSuffix('Handler');

/*
|--------------------------------------------------------------------------
| Task Subscribers Architecture
|--------------------------------------------------------------------------
|
| All task subscribers must have proper naming and structure.
|
*/

arch('task subscribers have Subscriber suffix')
    ->expect('App\Tasks\Subscribers')
    ->toHaveSuffix('Subscriber');

arch('task subscribers do not use debugging functions')
    ->expect('App\Tasks\Subscribers')
    ->not->toUse(['dd', 'dump', 'die', 'ray']);

/*
|--------------------------------------------------------------------------
| Task DTOs Architecture
|--------------------------------------------------------------------------
*/

arch('task DTOs are readonly')
    ->expect('App\Tasks\DTOs')
    ->toBeReadonly();

/*
|--------------------------------------------------------------------------
| Task Enums Architecture
|--------------------------------------------------------------------------
*/

arch('task enums are enums')
    ->expect('App\Tasks\Enums')
    ->toBeEnums();
