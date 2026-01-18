<?php

use App\Http\Controllers\Api\Admin\FileApiController;
use App\Http\Controllers\Api\Admin\InstitutionSubscriptionApiController;
use App\Http\Controllers\Api\Admin\MeetingApiController;
use App\Http\Controllers\Api\Admin\SharepointApiController;
use App\Http\Controllers\Api\Admin\TaskApiController;
use App\Http\Controllers\Api\Admin\TutorialApiController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\TypeController;
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
| API routes are automatically prefixed with '/api' and names with 'api.'
|
| Route organization:
| - /api/v1/* - Public API endpoints (no auth required)
| - /api/v1/admin/* - Admin API endpoints (auth required, session-based)
|
| Note: RouteServiceProvider adds 'api.' prefix to all route names automatically.
| So route('api.v1.types.index') maps to /api/v1/types
|
*/

Route::prefix('v1')->name('v1.')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Public API Routes
    |--------------------------------------------------------------------------
    |
    | These endpoints are publicly accessible without authentication.
    | Used by external consumers (tenant pages, widgets, etc.)
    |
    */

    Route::get('types', [TypeController::class, 'index'])->name('types.index');
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');

    // Typesense configuration for frontend search
    Route::get('typesense/config', function () {
        return response()->json(\App\Services\Typesense\TypesenseManager::getFrontendConfig());
    })->name('typesense.config');

    // Tenant-specific public content
    Route::prefix('tenants/{tenant:alias}')->name('tenants.')->group(function () {
        Route::get('news', [NewsController::class, 'index'])->name('news.index');
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin API Routes
    |--------------------------------------------------------------------------
    |
    | These endpoints require authentication and are used by the admin
    | interface for dynamic data loading (fetch/useFetch calls).
    |
    | Use these instead of Inertia props when:
    | - Data needs to be refreshed without page reload (polling, real-time)
    | - Data is loaded on-demand (click to load, infinite scroll)
    | - Cross-component data sharing that doesn't fit Inertia props
    |
    | Use Inertia props (lazy/defer) when:
    | - Data is page-bound and benefits from partial reloads
    | - Data should be included in browser history state
    | - Data is loaded on initial page render
    |
    */
    Route::prefix('admin')->name('admin.')->middleware(['web', 'auth'])->group(function () {
        // Tasks
        Route::get('tasks/indicator', [TaskApiController::class, 'indicator'])->name('tasks.indicator');

        // Meetings
        Route::get('meetings/recent', [MeetingApiController::class, 'recent'])->name('meetings.recent');

        // Files
        Route::get('files', [FileApiController::class, 'index'])->name('files.index');
        Route::get('files/allowed-types', [FileApiController::class, 'allowedTypes'])->name('files.allowedTypes');

        // Sharepoint / FileableFiles
        Route::get('fileables/{type}/{id}/files', [SharepointApiController::class, 'fileableFiles'])->name('fileables.files');
        Route::get('fileables/{type}/{id}/inherited', [SharepointApiController::class, 'inheritedFiles'])->name('fileables.inherited');
        Route::get('sharepoint/potential-fileables', [SharepointApiController::class, 'potentialFileables'])->name('sharepoint.potentialFileables');
        Route::get('sharepoint/drive-items', [SharepointApiController::class, 'driveItems'])->name('sharepoint.driveItems');

        // Tutorials
        Route::get('tutorials/progress', [TutorialApiController::class, 'progress'])->name('tutorials.progress');
        Route::post('tutorials/complete', [TutorialApiController::class, 'markComplete'])->name('tutorials.complete');
        Route::post('tutorials/reset', [TutorialApiController::class, 'resetTour'])->name('tutorials.reset');
        Route::post('tutorials/reset-all', [TutorialApiController::class, 'resetAll'])->name('tutorials.resetAll');

        // Typesense admin search configuration with scoped API keys
        Route::get('search/config', [\App\Http\Controllers\Api\Admin\SearchApiController::class, 'config'])->name('search.config');
        Route::post('search/refresh-key', [\App\Http\Controllers\Api\Admin\SearchApiController::class, 'refreshKey'])->name('search.refreshKey');

        // Institution subscription (follow/mute) management
        Route::prefix('institutions')->name('institutions.')->group(function () {
            Route::get('followed', [InstitutionSubscriptionApiController::class, 'followed'])->name('followed');
            Route::get('{institution}/subscription-status', [InstitutionSubscriptionApiController::class, 'status'])->name('subscription.status');
            Route::post('{institution}/follow', [InstitutionSubscriptionApiController::class, 'follow'])->name('follow');
            Route::delete('{institution}/follow', [InstitutionSubscriptionApiController::class, 'unfollow'])->name('unfollow');
            Route::post('{institution}/mute', [InstitutionSubscriptionApiController::class, 'mute'])->name('mute');
            Route::delete('{institution}/mute', [InstitutionSubscriptionApiController::class, 'unmute'])->name('unmute');
        });

        // Notification subscription preferences
        Route::post('notification-subscriptions/reset', [InstitutionSubscriptionApiController::class, 'reset'])->name('subscriptions.reset');
    });
});
