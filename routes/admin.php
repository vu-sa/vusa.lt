<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes are for the admin interface (/mano/*) and use Inertia.js
| for page rendering. All routes require authentication via the 'auth'
| middleware applied in the RouteServiceProvider.
|
| IMPORTANT: Routes that return JSON for fetch/AJAX calls should be defined
| in routes/api.php under the /api/v1/admin/* prefix. Keep this file for
| Inertia page routes and form submissions only.
|
| See routes/api.php for:
| - /api/v1/admin/tasks/indicator (TasksIndicator component)
| - /api/v1/admin/files (file browser AJAX)
| - /api/v1/admin/fileables/* (SharePoint file management)
| - /api/v1/admin/tutorials/progress (tutorial state)
|
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('profile', [DashboardController::class, 'userSettings'])->name('profile');
Route::inertia('administration', 'Admin/ShowAdministration')->name('administration')->middleware('can:access-administration');
Route::get('dashboard/atstovavimas', [DashboardController::class, 'atstovavimas'])->name('dashboard.atstovavimas');
Route::get('dashboard/svetaine', [DashboardController::class, 'svetaine'])->name('dashboard.svetaine');
Route::get('dashboard/reservations', [DashboardController::class, 'reservations'])->name('dashboard.reservations');

Route::patch('profile', [DashboardController::class, 'updateUserSettings'])->name('profile.update');
Route::patch('profile/password', [DashboardController::class, 'updatePassword'])->name('profile.updatePassword');
Route::patch('profile/notification-preferences', [DashboardController::class, 'updateNotificationPreferences'])->name('profile.updateNotificationPreferences');
Route::post('profile/notification-preferences/test-email', [DashboardController::class, 'sendTestNotificationEmail'])->name('profile.sendTestNotificationEmail');
Route::get('tasks', [DashboardController::class, 'userTasks'])->name('userTasks');
Route::get('institutionGraph', [DashboardController::class, 'institutionGraph'])->name('institutionGraph');

// System Status
Route::get('system-status', [SystemStatusController::class, 'index'])->name('systemStatus');

// Restore and permanent-delete routes
Route::patch('banners/{banner}/restore', [BannerController::class, 'restore'])->name('banners.restore')->withTrashed();
Route::delete('banners/{banner}/force-delete', [BannerController::class, 'forceDelete'])->name('banners.forceDelete')->withTrashed();
Route::patch('calendar/{calendar}/restore', [CalendarController::class, 'restore'])->name('calendar.restore')->withTrashed();
Route::delete('calendar/{calendar}/force-delete', [CalendarController::class, 'forceDelete'])->name('calendar.forceDelete')->withTrashed();
Route::patch('categories/{category}/restore', [CategoryController::class, 'restore'])->name('categories.restore')->withTrashed();
Route::delete('categories/{category}/force-delete', [CategoryController::class, 'forceDelete'])->name('categories.forceDelete')->withTrashed();
Route::patch('duties/{duty}/restore', [DutyController::class, 'restore'])->name('duties.restore')->withTrashed();
Route::delete('duties/{duty}/force-delete', [DutyController::class, 'forceDelete'])->name('duties.forceDelete')->withTrashed();
Route::patch('forms/{form}/restore', [FormController::class, 'restore'])->name('forms.restore')->withTrashed();
Route::delete('forms/{form}/force-delete', [FormController::class, 'forceDelete'])->name('forms.forceDelete')->withTrashed();
Route::patch('institutions/{institution}/restore', [InstitutionController::class, 'restore'])->name('institutions.restore')->withTrashed();
Route::delete('institutions/{institution}/force-delete', [InstitutionController::class, 'forceDelete'])->name('institutions.forceDelete')->withTrashed();
Route::patch('meetings/{meeting}/restore', [MeetingController::class, 'restore'])->name('meetings.restore')->withTrashed();
Route::delete('meetings/{meeting}/force-delete', [MeetingController::class, 'forceDelete'])->name('meetings.forceDelete')->withTrashed();
Route::patch('navigation/{navigation}/restore', [NavigationController::class, 'restore'])->name('navigation.restore')->withTrashed();
Route::delete('navigation/{navigation}/force-delete', [NavigationController::class, 'forceDelete'])->name('navigation.forceDelete')->withTrashed();
Route::patch('news/{news}/restore', [NewsController::class, 'restore'])->name('news.restore')->withTrashed();
Route::delete('news/{news}/force-delete', [NewsController::class, 'forceDelete'])->name('news.forceDelete')->withTrashed();
Route::post('news/{news}/duplicate', [NewsController::class, 'duplicate'])->name('news.duplicate');
Route::patch('pages/{page}/restore', [PageController::class, 'restore'])->name('pages.restore')->withTrashed();
Route::delete('pages/{page}/force-delete', [PageController::class, 'forceDelete'])->name('pages.forceDelete')->withTrashed();
Route::patch('problems/{problem}/restore', [ProblemController::class, 'restore'])->name('problems.restore')->withTrashed();
Route::delete('problems/{problem}/force-delete', [ProblemController::class, 'forceDelete'])->name('problems.forceDelete')->withTrashed();
Route::patch('quickLinks/{quickLink}/restore', [QuickLinkController::class, 'restore'])->name('quickLinks.restore')->withTrashed();
Route::delete('quickLinks/{quickLink}/force-delete', [QuickLinkController::class, 'forceDelete'])->name('quickLinks.forceDelete')->withTrashed();
Route::post('meetings/{meeting}/institutions', [MeetingController::class, 'attachInstitution'])->name('meetings.institutions.attach');
Route::delete('meetings/{meeting}/institutions/{institution}', [MeetingController::class, 'detachInstitution'])->name('meetings.institutions.detach');
Route::post('meetings/{meeting}/calendar-event', [MeetingCalendarController::class, 'store'])->name('meetings.calendarEvent.store');
Route::delete('meetings/{meeting}/calendar-event', [MeetingCalendarController::class, 'destroy'])->name('meetings.calendarEvent.destroy');
Route::post('meetings/{meeting}/documents', [MeetingDocumentController::class, 'store'])->name('meetings.documents.store');
Route::post('meetings/{meeting}/documents/sharepoint', [MeetingDocumentController::class, 'storeFromSharepoint'])->name('meetings.documents.storeFromSharepoint');
Route::delete('meetings/{meeting}/documents/{document}', [MeetingDocumentController::class, 'destroy'])->name('meetings.documents.destroy');
Route::patch('reservations/{reservation}/restore', [ReservationController::class, 'restore'])->name('reservations.restore')->withTrashed();
Route::delete('reservations/{reservation}/force-delete', [ReservationController::class, 'forceDelete'])->name('reservations.forceDelete')->withTrashed();
Route::patch('resources/{resource}/restore', [ResourceController::class, 'restore'])->name('resources.restore')->withTrashed();
Route::delete('resources/{resource}/force-delete', [ResourceController::class, 'forceDelete'])->name('resources.forceDelete')->withTrashed();
Route::patch('studyPrograms/{studyProgram}/restore', [StudyProgramController::class, 'restore'])->name('studyPrograms.restore')->withTrashed();
Route::delete('studyPrograms/{studyProgram}/force-delete', [StudyProgramController::class, 'forceDelete'])->name('studyPrograms.forceDelete')->withTrashed();
Route::patch('studySets/{studySet}/restore', [StudySetController::class, 'restore'])->name('studySets.restore')->withTrashed();
Route::delete('studySets/{studySet}/force-delete', [StudySetController::class, 'forceDelete'])->name('studySets.forceDelete')->withTrashed();
Route::patch('tags/{tag}/restore', [TagController::class, 'restore'])->name('tags.restore')->withTrashed();
Route::delete('tags/{tag}/force-delete', [TagController::class, 'forceDelete'])->name('tags.forceDelete')->withTrashed();
Route::patch('types/{type}/restore', [TypeController::class, 'restore'])->name('types.restore')->withTrashed();
Route::delete('types/{type}/force-delete', [TypeController::class, 'forceDelete'])->name('types.forceDelete')->withTrashed();
Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();
Route::delete('users/{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.forceDelete')->withTrashed();

// Resources with Precognition (live validation)
Route::resource('pages', PageController::class)->except(['show'])
    ->middleware(HandlePrecognitiveRequests::class);
Route::resource('news', NewsController::class)->except(['show'])
    ->middleware(HandlePrecognitiveRequests::class);
Route::resource('categories', CategoryController::class)->except(['show']);
Route::resource('tags', TagController::class)->except(['show']);
Route::get('tags/merge', [TagController::class, 'mergeTags'])->name('tags.merge');
Route::post('tags/merge', [TagController::class, 'processMergeTags'])->name('tags.processMerge');

Route::post('quickLinks/update-order', [QuickLinkController::class, 'updateOrder'])->name('quickLinks.update-order');
Route::resource('quickLinks', QuickLinkController::class)->except(['show']);
Route::resource('banners', BannerController::class)->except(['show']);
Route::resource('navigation', NavigationController::class)->except(['show']);
Route::post('navigation/updateOrder', [NavigationController::class, 'updateOrder'])->name('navigation.updateOrder');

Route::get('users/merge', [UserController::class, 'merge'])->name('users.merge');
Route::post('users/merge', [UserController::class, 'mergeUsers'])->name('users.mergeUsers');
Route::resource('users', UserController::class);

Route::post('users/{user}/generate-password', [UserController::class, 'generatePassword'])->name('users.generatePassword');
Route::delete('users/{user}/delete-password', [UserController::class, 'deletePassword'])->name('users.deletePassword');
Route::resource('users.comments', CommentController::class)->only(['store', 'update', 'destroy']);

Route::get('notifications', [UserNotificationsController::class, 'index'])->name('notifications.index');
Route::post('notification/{id}/markAsRead', [UserNotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
Route::post('notification/mark-all-as-read', [UserNotificationsController::class, 'markAllAsRead'])->name('notifications.mark-as-read.all');
Route::delete('notification/{id}', [UserNotificationsController::class, 'destroy'])->name('notifications.destroy');
Route::delete('notifications', [UserNotificationsController::class, 'destroyAll'])->name('notifications.destroy-all');

// Push notification subscriptions
Route::get('push-subscription', [PushSubscriptionController::class, 'index'])->name('push-subscription.index');
Route::post('push-subscription', [PushSubscriptionController::class, 'store'])->name('push-subscription.store');
Route::delete('push-subscription', [PushSubscriptionController::class, 'destroy'])->name('push-subscription.destroy');
Route::delete('push-subscription/{id}', [PushSubscriptionController::class, 'destroyById'])->name('push-subscription.destroyById');
Route::post('push-subscription/test', [PushSubscriptionController::class, 'sendTest'])->name('push-subscription.test');

Route::resource('calendar', CalendarController::class)
    ->middleware(HandlePrecognitiveRequests::class);
Route::post('calendar/{calendar}/media/{media}', [CalendarController::class, 'destroyMedia'])->name('calendar.destroyMedia');
Route::post('calendar/{calendar}/duplicate', [CalendarController::class, 'duplicate'])->name('calendar.duplicate');
Route::resource('agendaItems', AgendaItemController::class)->except(['index', 'create']);
Route::post('agendaItems/reorder', [AgendaItemController::class, 'reorder'])->name('agendaItems.reorder');
Route::resource('votes', VoteController::class)->except(['index', 'create', 'show', 'edit']);
Route::post('votes/{vote}/set-main', [VoteController::class, 'setMain'])->name('votes.setMain');
Route::resource('meetings', MeetingController::class)->except(['create']);
Route::get('meetings-search', [MeetingController::class, 'search'])->name('meetings.search');

// Faceted search pages (uses scoped Typesense API keys for authorization)
Route::prefix('search')->name('search.')->group(function (): void {
    Route::get('/', [SearchController::class, 'index'])->name('index');
    Route::get('meetings', [SearchController::class, 'meetings'])->name('meetings');
    Route::get('agenda-items', [SearchController::class, 'agendaItems'])->name('agendaItems');
    Route::get('institutions', [SearchController::class, 'institutions'])->name('institutions');
    Route::get('resources', [SearchController::class, 'resources'])->name('resources');
});

// Check-in actions for institutions
Route::post('institutions/{institution}/check-ins', [InstitutionCheckInController::class, 'store'])->name('institutions.check-ins.store');
Route::delete('institutions/{institution}/check-ins/active', [InstitutionCheckInController::class, 'destroyActive'])->name('institutions.check-ins.destroyActive');

Route::resource('resources', ResourceController::class);
Route::resource('resourceCategories', ResourceCategoryController::class);

Route::put('reservations/{reservation}/add-users', [ReservationController::class, 'addUsers'])->name('reservations.add-users');
// Reservations are never updated directly — every mutation goes through the
// reservationResources pivot below, so `edit`/`update` are not registered.
Route::resource('reservations', ReservationController::class)->except(['edit', 'update']);
Route::resource('reservationResources', ReservationResourceController::class)->except(['index', 'create', 'edit']);

// Approval routes
Route::post('approvals', [ApprovalController::class, 'store'])->name('approvals.store');
Route::post('approvals/bulk', [ApprovalController::class, 'bulkStore'])->name('approvals.bulkStore');
Route::post('approvals/resolve', [ApprovalController::class, 'resolve'])->name('approvals.resolve');
Route::get('approvals/history', [ApprovalController::class, 'history'])->name('approvals.history');

// File management routes
// GET endpoints moved to API: route('api.v1.admin.files.index'), route('api.v1.admin.files.allowedTypes')
Route::post('files/createDirectory', [FilesController::class, 'createDirectory'])->name('files.createDirectory');
Route::delete('files/deleteDirectory', [FilesController::class, 'deleteDirectory'])->name('files.deleteDirectory');
Route::post('files/upload-image', [FilesController::class, 'uploadImage'])->name('files.uploadImage');
Route::delete('files/delete', [FilesController::class, 'delete'])->name('files.delete');
Route::delete('files/bulk-delete', [FilesController::class, 'bulkDelete'])->name('files.bulkDelete');
Route::post('files/scan-usage', [FilesController::class, 'scanFileUsage'])->name('files.scanUsage');
// FilesController only implements index and store; the other resource verbs were
// registered but had no method behind them.
Route::resource('files', FilesController::class)->only(['index', 'store']);
Route::post('files/compress', [FilesController::class, 'compressImage'])->name('files.compress');

Route::resource('documents', DocumentController::class)->except('create', 'edit');
Route::post('documents/{document}/refresh', [DocumentController::class, 'refresh'])->name('documents.refresh');
Route::post('documents/bulk-sync', [DocumentController::class, 'bulkSync'])->name('documents.bulk-sync');

Route::get('duties/merge', [DutyController::class, 'merge'])->name('duties.merge');
Route::post('duties/merge', [DutyController::class, 'mergeDuties'])->name('duties.mergeDuties');
Route::resource('duties', DutyController::class);
Route::get('duties-update-users', [DutyController::class, 'updateUsersWizard'])->name('duties.updateUsersWizard');
Route::post('duties/{duty}/batch-update-users', [DutyController::class, 'batchUpdateUsers'])->name('duties.batchUpdateUsers');
// DutiableController has no create/store — dutiables are created through the duty and
// user flows, not directly.
// Declared before the resource so /dutiables/timeline can never be read as /dutiables/{dutiable}.
Route::get('dutiables/timeline', [DutiableTimelineController::class, 'index'])->name('dutiables.timeline');
Route::post('dutiables/timeline/apply', [DutiableTimelineController::class, 'apply'])->name('dutiables.timeline.apply');
Route::post('dutiables/timeline/merge', [DutiableTimelineController::class, 'merge'])->name('dutiables.timeline.merge');
Route::resource('dutiables', DutiableController::class)->only(['edit', 'update', 'destroy']);
Route::get('studyPrograms/merge', [StudyProgramController::class, 'merge'])->name('studyPrograms.merge');
Route::post('studyPrograms/merge', [StudyProgramController::class, 'mergeStudyPrograms'])->name('studyPrograms.mergeStudyPrograms');
Route::resource('studyPrograms', StudyProgramController::class)->except(['show']);
Route::resource('studySets', StudySetController::class)->except(['show']);
Route::post('institutions/reorderDuties', [InstitutionController::class, 'reorderDuties'])->name('institutions.reorderDuties');
Route::resource('institutions', InstitutionController::class);

Route::get('tenants/{tenant}/quick-links/edit', [TenantController::class, 'editQuickLink'])->name('tenants.editQuickLink');
Route::post('tenants/{tenant}/quick-links', [TenantController::class, 'updateQuickLink'])->name('tenants.updateQuickLink');
Route::get('tenants/{tenant}/main-page/edit', [TenantController::class, 'editMainPage'])->name('tenants.editMainPage');
Route::post('tenants/{tenant}/main-page', [TenantController::class, 'updateMainPage'])->name('tenants.updateMainPage');

Route::resource('tenants', TenantController::class);

Route::get('forms/{form}/export', [FormController::class, 'export'])->name('forms.export');
Route::resource('forms', FormController::class);

Route::patch('problems/{problem}/status', [ProblemController::class, 'updateStatus'])->name('problems.updateStatus');
Route::resource('problems', ProblemController::class);

Route::resource('types', TypeController::class);
Route::resource('relationships', RelationshipController::class);
Route::post('relationships/{relationship}/storeModelRelationship', [RelationshipController::class, 'storeModelRelationship'])->name('relationships.storeModelRelationship');
Route::patch('relationships/relationshipables/{relationshipable}', [RelationshipController::class, 'updateModelRelationship'])->name('relationships.updateModelRelationship');
Route::delete('relationships/relationshipables/{relationshipable}', [RelationshipController::class, 'deleteModelRelationship'])->name('relationships.deleteModelRelationship');
Route::resource('roles', RoleController::class);
Route::patch('roles/{role}/attach/{model}/permissions', [RoleController::class, 'syncPermissionGroup'])->name('roles.syncPermissionGroup');
Route::put('roles/{role}/sync/duties', [RoleController::class, 'syncDuties'])->name('roles.syncDuties');
Route::put('roles/{role}/sync/attachableTypes', [RoleController::class, 'syncAttachableTypes'])->name('roles.syncAttachableTypes');
Route::resource('permissions', PermissionController::class)->only(['index']);
Route::resource('tasks', TaskController::class)->except(['index', 'create', 'show', 'edit']);
Route::get('tasks/summary', [TaskController::class, 'summary'])->name('tasks.summary');
Route::post('tasks/{task}/updateCompletionStatus', [TaskController::class, 'updateCompletionStatus'])->name('tasks.updateCompletionStatus');
// GET tasks/indicator moved to API: route('api.v1.admin.tasks.indicator')

Route::resource('sharepointFiles', SharepointFileController::class)->except('create', 'show', 'edit', 'update');

// FileableFiles - local metadata-based file management
// GET endpoints moved to API: route('api.v1.admin.fileables.files'), route('api.v1.admin.fileables.inherited')
Route::delete('fileableFiles/{fileableFile}', [SharepointFileController::class, 'destroyFileableFile'])->name('fileableFiles.destroy');

// SharePoint integration
// GET endpoints moved to API: route('api.v1.admin.sharepoint.potentialFileables'), route('api.v1.admin.sharepoint.driveItems')
Route::post('sharepoint/createFolder', [SharepointFileController::class, 'createFolder'])->name('sharepoint.createFolder');
Route::get('sharepoint/{id}/permissions', [SharepointFileController::class, 'getDriveItemPublicLink'])->name('sharepoint.getDriveItemPublicLink');
Route::get('sharepoint/{type}/{id}', [SharepointFileController::class, 'getTypesDriveItems'])->name('sharepoint.getTypesDriveItems');
Route::post('sharepoint/{id}/permissions/createPublic', [SharepointFileController::class, 'createPublicPermission'])->name('sharepoint.createPublicPermission');

// Settings routes
Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::get('settings/forms', [SettingsController::class, 'editFormSettings'])->name('settings.forms.edit');
Route::post('settings/forms', [SettingsController::class, 'updateFormSettings'])->name('settings.forms.update');
Route::get('settings/meetings', [SettingsController::class, 'editMeetingSettings'])->name('settings.meetings.edit');
Route::post('settings/meetings', [SettingsController::class, 'updateMeetingSettings'])->name('settings.meetings.update');
Route::get('settings/documents', [SettingsController::class, 'editDocumentSettings'])->name('settings.documents.edit');
Route::post('settings/documents', [SettingsController::class, 'updateDocumentSettings'])->name('settings.documents.update');
Route::get('settings/atstovavimas', [SettingsController::class, 'editAtstovavimasSettings'])->name('settings.atstovavimas.edit');
Route::post('settings/atstovavimas', [SettingsController::class, 'updateAtstovavimasSettings'])->name('settings.atstovavimas.update');
Route::get('settings/site', [SettingsController::class, 'editSiteSettings'])->name('settings.site.edit');
Route::post('settings/site', [SettingsController::class, 'updateSiteSettings'])->name('settings.site.update');
Route::get('settings/cadences', [CadenceController::class, 'index'])->name('settings.cadences.index');
Route::post('settings/cadences/defaults', [CadenceController::class, 'updateDefaults'])->name('settings.cadences.defaults');
Route::post('settings/cadences', [CadenceController::class, 'store'])->name('settings.cadences.store');
Route::patch('settings/cadences/{cadence}', [CadenceController::class, 'update'])->name('settings.cadences.update');
Route::delete('settings/cadences/{cadence}', [CadenceController::class, 'destroy'])->name('settings.cadences.destroy');
Route::get('settings/authorization', [SettingsController::class, 'editAuthorization'])->name('settings.authorization.edit');
Route::post('settings/authorization', [SettingsController::class, 'updateAuthorization'])->name('settings.authorization.update');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
