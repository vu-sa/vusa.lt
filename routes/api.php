<?php

use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\NewsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
| IMPORTANT: All API endpoints should be defined here, not in web.php
| This ensures proper organization and middleware application.
| API routes are automatically prefixed with '/api'
|
*/

Route::prefix('v1')->group(function () {
    // Route::middleware(['auth:api'])->group(function () {
    // Route::apiResource('goals', 'GoalController')->only(['index']);
    // });
    Route::apiResource('types', 'TypeController')->only(['index']);
    Route::apiResource('documents', 'DocumentController')->only(['index']);
    
    // Simple Typesense configuration endpoint for Phase 1
    Route::get('typesense/config', function () {
        return response()->json(\App\Services\Typesense\TypesenseManager::getFrontendConfig());
    })->name('typesense.config');
    
    Route::group(['prefix' => '{lang}', 'where' => ['lang' => 'lt|en']], function () {
        Route::get('news/{tenant:alias}', [NewsController::class, 'getTenantNews'])->name('news.tenant.index');
        Route::get('calendar/{tenant:alias}', [CalendarController::class, 'getTenantCalendar'])->name('calendar.tenant.index');
    });
});
