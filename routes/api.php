<?php

use App\Http\Controllers\Api\Admin\AcademicCalendarApiController;
use App\Http\Controllers\Api\Admin\AgendaItemNoteController;
use App\Http\Controllers\Api\Admin\CommentApiController;
use App\Http\Controllers\Api\Admin\CommentPollVoteApiController;
use App\Http\Controllers\Api\Admin\CommentReactionApiController;
use App\Http\Controllers\Api\Admin\FileApiController;
use App\Http\Controllers\Api\Admin\ImpersonateApiController;
use App\Http\Controllers\Api\Admin\InstitutionApiController;
use App\Http\Controllers\Api\Admin\InstitutionSubscriptionApiController;
use App\Http\Controllers\Api\Admin\MeetingApiController;
use App\Http\Controllers\Api\Admin\ResourceApiController;
use App\Http\Controllers\Api\Admin\ResourceAvailabilityApiController;
use App\Http\Controllers\Api\Admin\SearchApiController;
use App\Http\Controllers\Api\Admin\SharepointApiController;
use App\Http\Controllers\Api\Admin\TaskApiController;
use App\Http\Controllers\Api\Admin\TextBoxSubmissionApiController;
use App\Http\Controllers\Api\Admin\TutorialApiController;
use App\Http\Controllers\Api\Admin\UserPreferencesController;
use App\Http\Controllers\Api\Admin\UserSearchApiController;
use App\Http\Controllers\Api\Admin\WorkspaceApiController;
use App\Http\Controllers\Api\Admin\WorkspaceDocumentController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\TextBoxSubmissionController;
use App\Http\Controllers\Api\TypeController;
use App\Services\Typesense\TypesenseManager;
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
        return response()->json(TypesenseManager::getFrontendConfig());
    })->name('typesense.config');

    // Text box submissions (public) - 'web' middleware needed to read session for optional user association
    Route::post('text-box-submissions', [TextBoxSubmissionController::class, 'store'])
        ->middleware(['web', 'throttle:textBoxSubmissions'])
        ->name('text-box-submissions.store');

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

        // Academic vacation periods (shaded in the meetings Gantt chart)
        Route::get('academic-calendar/vacations', [AcademicCalendarApiController::class, 'vacations'])->name('academicCalendar.vacations');

        // Meetings
        Route::get('meetings/recent', [MeetingApiController::class, 'recent'])->name('meetings.recent');
        Route::get('meetings/{meeting}/preview', [MeetingApiController::class, 'preview'])->name('meetings.preview');

        // Institutions
        Route::get('institutions/{institution}/preview', [InstitutionApiController::class, 'preview'])->name('institutions.preview');

        // Agenda item collaborative notes ("Atstovų pastabos")
        Route::get('agenda-items/{agendaItem}/note', [AgendaItemNoteController::class, 'show'])->name('agendaItems.note.show');
        Route::put('agenda-items/{agendaItem}/note', [AgendaItemNoteController::class, 'update'])->name('agendaItems.note.update');

        // Workspace collaborative documents. Realtime sync is peer-to-peer over
        // the workspace-documents.{id} presence channel; the state endpoints
        // hydrate late joiners and persist durable snapshots.
        Route::get('workspaces/{workspace}/documents', [WorkspaceDocumentController::class, 'index'])->name('workspaces.documents.index');
        Route::post('workspaces/{workspace}/documents', [WorkspaceDocumentController::class, 'store'])->name('workspaces.documents.store');
        Route::patch('workspaces/{workspace}/documents/{document}', [WorkspaceDocumentController::class, 'update'])->name('workspaces.documents.update');
        Route::delete('workspaces/{workspace}/documents/{document}', [WorkspaceDocumentController::class, 'destroy'])->name('workspaces.documents.destroy');
        Route::get('workspaces/{workspace}/documents/{document}/state', [WorkspaceDocumentController::class, 'showState'])->name('workspaces.documents.state.show');
        Route::put('workspaces/{workspace}/documents/{document}/state', [WorkspaceDocumentController::class, 'updateState'])->name('workspaces.documents.state.update');

        // Workspace management dialogs (member invites, record linking)
        Route::get('workspaces/{workspace}/member-candidates', [WorkspaceApiController::class, 'memberCandidates'])->name('workspaces.memberCandidates');
        Route::get('workspaces/{workspace}/link-candidates', [WorkspaceApiController::class, 'linkCandidates'])->name('workspaces.linkCandidates');

        // Discussions (polymorphic comment threads). Read/write follow the
        // parent's `view` ability. {commentableType} is resolved through the
        // App\Support\Commentables allowlist.
        Route::get('discussions/feed', [CommentApiController::class, 'feed'])->name('comments.feed');
        Route::get('discussions/{commentableType}/{commentableId}', [CommentApiController::class, 'index'])->name('comments.index');
        Route::post('discussions/{commentableType}/{commentableId}/comments', [CommentApiController::class, 'store'])->name('comments.store');
        Route::get('discussions/{commentableType}/{commentableId}/mentionables', [CommentApiController::class, 'mentionables'])->name('comments.mentionables');
        Route::patch('comments/{comment}', [CommentApiController::class, 'update'])->name('comments.update');
        Route::delete('comments/{comment}', [CommentApiController::class, 'destroy'])->name('comments.destroy');
        Route::post('comments/{comment}/resolve', [CommentApiController::class, 'resolve'])->name('comments.resolve');
        Route::delete('comments/{comment}/resolve', [CommentApiController::class, 'unresolve'])->name('comments.unresolve');
        Route::put('comments/{comment}/reactions', [CommentReactionApiController::class, 'toggle'])->name('comments.reactions.toggle');
        Route::put('comments/{comment}/poll/votes', [CommentPollVoteApiController::class, 'toggle'])->name('comments.poll.votes.toggle');

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
        Route::get('search/config', [SearchApiController::class, 'config'])->name('search.config');
        Route::post('search/refresh-key', [SearchApiController::class, 'refreshKey'])->name('search.refreshKey');

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

        // User preferences (sidebar customization, recent pages)
        Route::patch('user-preferences', [UserPreferencesController::class, 'updateUIPreferences'])->name('user-preferences.update');
        Route::patch('user-preferences/recent-page', [UserPreferencesController::class, 'trackRecentPage'])->name('user-preferences.trackRecentPage');

        // User search for forms (e.g. responsible user in problems)
        Route::get('users/search', [UserSearchApiController::class, 'search'])->name('users.search');

        // Date-range resource availability for the reservation resource picker
        Route::post('resources/availability', [ResourceAvailabilityApiController::class, 'index'])->name('resources.availability');

        // Resource detail preview (reservations + managers) for the admin search pane
        Route::get('resources/{resource}/preview', [ResourceApiController::class, 'preview'])->name('resources.preview');

        // Impersonation (local/staging only, super admins)
        Route::prefix('impersonate')->name('impersonate.')->group(function () {
            Route::get('search', [ImpersonateApiController::class, 'search'])->name('search');
            Route::post('start', [ImpersonateApiController::class, 'start'])->name('start');
            Route::post('stop', [ImpersonateApiController::class, 'stop'])->name('stop');
        });

        // Text box submissions
        Route::get('text-box-submissions', [TextBoxSubmissionApiController::class, 'index'])->name('text-box-submissions.index');
        Route::get('text-box-submissions/export', [TextBoxSubmissionApiController::class, 'export'])->name('text-box-submissions.export');
        Route::delete('text-box-submissions/{submission}', [TextBoxSubmissionApiController::class, 'destroy'])->name('text-box-submissions.destroy');
        Route::delete('text-box-submissions', [TextBoxSubmissionApiController::class, 'destroyAll'])->name('text-box-submissions.destroyAll');
    });
});
