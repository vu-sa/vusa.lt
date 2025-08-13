<?php

use App\Http\Controllers\Admin\MeetingController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\InstitutionCheckInController;
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
    // Authenticated endpoints using token guard
    Route::middleware(['auth:api'])->group(function () {
        // Meeting suggestions
    Route::get('meetings/suggestions', [MeetingController::class, 'suggestions'])->name('meetings.suggestions');
        
        // Check-ins: create under an institution, and actions by check-in id
        Route::post('institutions/{institution}/check-ins', [InstitutionCheckInController::class, 'store'])->name('institutions.check-ins.store');
        Route::post('check-ins/{checkIn}/confirm', [InstitutionCheckInController::class, 'confirm'])->name('check-ins.confirm');
        Route::post('check-ins/{checkIn}/withdraw', [InstitutionCheckInController::class, 'withdraw'])->name('check-ins.withdraw');
        Route::post('check-ins/{checkIn}/dispute', [InstitutionCheckInController::class, 'dispute'])->name('check-ins.dispute');
        Route::post('check-ins/{checkIn}/resolve', [InstitutionCheckInController::class, 'resolve'])->name('check-ins.resolve');
        Route::post('check-ins/{checkIn}/suppress', [InstitutionCheckInController::class, 'suppress'])->name('check-ins.suppress');
        Route::post('check-ins/{checkIn}/unsuppress', [InstitutionCheckInController::class, 'unsuppress'])->name('check-ins.unsuppress');
    Route::post('check-ins/{checkIn}/flag', [InstitutionCheckInController::class, 'flag'])->name('check-ins.flag');
    });
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
