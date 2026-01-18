<?php

namespace App\Http\Controllers\Admin;

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
Route::get('tasks', [DashboardController::class, 'userTasks'])->name('userTasks');
Route::get('institutionGraph', [DashboardController::class, 'institutionGraph'])->name('institutionGraph');

// System Status
Route::get('system-status', [SystemStatusController::class, 'index'])->name('systemStatus');

// Restore routes
Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();
Route::patch('pages/{page}/restore', [PageController::class, 'restore'])->name('pages.restore')->withTrashed();
Route::patch('news/{news}/restore', [NewsController::class, 'restore'])->name('news.restore')->withTrashed();
Route::post('news/{news}/duplicate', [NewsController::class, 'duplicate'])->name('news.duplicate');
Route::patch('duties/{duty}/restore', [DutyController::class, 'restore'])->name('duties.restore')->withTrashed();
Route::patch('institutions/{institution}/restore', [InstitutionController::class, 'restore'])->name('institutions.restore')->withTrashed();
Route::patch('meetings/{meeting}/restore', [MeetingController::class, 'restore'])->name('meetings.restore')->withTrashed();
Route::patch('reservations/{reservation}/restore', [ReservationController::class, 'restore'])->name('reservations.restore')->withTrashed();
Route::patch('resources/{resource}/restore', [ResourceController::class, 'restore'])->name('resources.restore')->withTrashed();
Route::patch('types/{type}/restore', [TypeController::class, 'restore'])->name('types.restore')->withTrashed();

// Resources with Precognition (live validation)
Route::resource('pages', PageController::class)->except(['show'])
    ->middleware(\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class);
Route::resource('news', NewsController::class)->except(['show'])
    ->middleware(\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class);
Route::resource('categories', CategoryController::class)->except(['show']);
Route::resource('tags', TagController::class)->except(['show']);
Route::get('tags/merge', [TagController::class, 'mergeTags'])->name('tags.merge');
Route::post('tags/merge', [TagController::class, 'processMergeTags'])->name('tags.processMerge');

// change order main page
Route::get('quickLinks/tenant/{tenant}/edit-order/{lang}', [QuickLinkController::class, 'editOrder'])->name('quickLinks.edit-order')
    ->whereIn('lang', ['lt', 'en']);

Route::post('quickLinks/update-order', [QuickLinkController::class, 'updateOrder'])->name('quickLinks.update-order');
Route::resource('quickLinks', QuickLinkController::class)->except(['show']);
Route::resource('banners', BannerController::class)->except(['show']);
Route::resource('navigation', NavigationController::class)->except(['show']);
/* Route::get('navigation/editAll', [NavigationController::class, 'editAll'])->name('navigation.editAll'); */
Route::post('navigation/updateOrder', [NavigationController::class, 'updateOrder'])->name('navigation.updateOrder');
Route::post('navigation/updateColumn', [NavigationController::class, 'updateColumn'])->name('navigation.updateColumn');

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

Route::resource('memberships', MembershipController::class);
Route::post('memberships/{membership}/users/import', [MembershipController::class, 'importUsers'])->name('membershipUsers.import');

Route::resource('trainings', TrainingController::class);
Route::get('trainings/{training}/registration', [TrainingController::class, 'showRegistration'])->name('trainings.showRegistration');

Route::resource('programmes', ProgrammeController::class);
Route::resource('programmeDays', ProgrammeDayController::class)->only(['destroy']);
Route::resource('programmeBlocks', ProgrammeBlockController::class)->only(['destroy']);

Route::resource('programmeParts', ProgrammePartController::class)->only(['destroy']);
Route::post('programmeParts/{programmePart}/attach', [ProgrammePartController::class, 'attach'])->name('programmeParts.attach');
Route::post('programmeParts/{programmePart}/detach', [ProgrammePartController::class, 'detach'])->name('programmeParts.detach');

Route::resource('programmeSections', ProgrammeSectionController::class)->only(['destroy']);
Route::post('programmeSections/{programmeSection}/attach', [ProgrammeSectionController::class, 'attach'])->name('programmeSections.attach');
Route::post('programmeSections/{programmeSection}/detach', [ProgrammeSectionController::class, 'detach'])->name('programmeSections.detach');

Route::resource('calendar', CalendarController::class)
    ->middleware(\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class);
Route::post('calendar/{calendar}/media/{media}', [CalendarController::class, 'destroyMedia'])->name('calendar.destroyMedia');
Route::post('calendar/{calendar}/duplicate', [CalendarController::class, 'duplicate'])->name('calendar.duplicate');
Route::resource('agendaItems', AgendaItemController::class)->except(['index', 'create']);
Route::post('agendaItems/reorder', [AgendaItemController::class, 'reorder'])->name('agendaItems.reorder');
Route::resource('meetings', MeetingController::class)->except(['create']);
Route::get('meetings-search', [MeetingController::class, 'search'])->name('meetings.search');

// Faceted search pages (uses scoped Typesense API keys for authorization)
Route::prefix('search')->name('search.')->group(function () {
    Route::get('meetings', [SearchController::class, 'meetings'])->name('meetings');
    Route::get('agenda-items', [SearchController::class, 'agendaItems'])->name('agendaItems');
    Route::get('institutions', [SearchController::class, 'institutions'])->name('institutions');
});

// Check-in actions for institutions
Route::post('institutions/{institution}/check-ins', [InstitutionCheckInController::class, 'store'])->name('institutions.check-ins.store');
Route::delete('institutions/{institution}/check-ins/active', [InstitutionCheckInController::class, 'destroyActive'])->name('institutions.check-ins.destroyActive');

Route::resource('resources', ResourceController::class);
Route::resource('resourceCategories', ResourceCategoryController::class);

Route::put('reservations/{reservation}/add-users', [ReservationController::class, 'addUsers'])->name('reservations.add-users');
Route::resource('reservations', ReservationController::class);
Route::resource('reservationResources', ReservationResourceController::class)->except(['index', 'create', 'edit']);

// Approval routes
Route::post('approvals', [ApprovalController::class, 'store'])->name('approvals.store');
Route::post('approvals/bulk', [ApprovalController::class, 'bulkStore'])->name('approvals.bulkStore');
Route::get('approvals/history', [ApprovalController::class, 'history'])->name('approvals.history');

// File management routes
// GET endpoints moved to API: route('api.v1.admin.files.index'), route('api.v1.admin.files.allowedTypes')
Route::post('files/createDirectory', [FilesController::class, 'createDirectory'])->name('files.createDirectory');
Route::delete('files/deleteDirectory', [FilesController::class, 'deleteDirectory'])->name('files.deleteDirectory');
Route::post('files/upload-image', [FilesController::class, 'uploadImage'])->name('files.uploadImage');
Route::delete('files/delete', [FilesController::class, 'delete'])->name('files.delete');
Route::delete('files/bulk-delete', [FilesController::class, 'bulkDelete'])->name('files.bulkDelete');
Route::post('files/scan-usage', [FilesController::class, 'scanFileUsage'])->name('files.scanUsage');
Route::resource('files', FilesController::class);
Route::post('files/compress', [FilesController::class, 'compressImage'])->name('files.compress');

Route::resource('documents', DocumentController::class)->except('create', 'edit');
Route::post('documents/{document}/refresh', [DocumentController::class, 'refresh'])->name('documents.refresh');
Route::post('documents/bulk-sync', [DocumentController::class, 'bulkSync'])->name('documents.bulk-sync');

Route::resource('duties', DutyController::class);
Route::get('duties-update-users', [DutyController::class, 'updateUsersWizard'])->name('duties.updateUsersWizard');
Route::post('duties/{duty}/batch-update-users', [DutyController::class, 'batchUpdateUsers'])->name('duties.batchUpdateUsers');
Route::resource('dutiables', DutiableController::class)->except(['index', 'show']);
Route::get('studyPrograms/merge', [StudyProgramController::class, 'merge'])->name('studyPrograms.merge');
Route::post('studyPrograms/merge', [StudyProgramController::class, 'mergeStudyPrograms'])->name('studyPrograms.mergeStudyPrograms');
Route::resource('studyPrograms', StudyProgramController::class)->except(['show']);
Route::post('institutions/reorderDuties', [InstitutionController::class, 'reorderDuties'])->name('institutions.reorderDuties');
Route::resource('institutions', InstitutionController::class);

Route::get('tenants/{tenant}/quick-links/edit', [TenantController::class, 'editQuickLink'])->name('tenants.editQuickLink');
Route::post('tenants/{tenant}/quick-links', [TenantController::class, 'updateQuickLink'])->name('tenants.updateQuickLink');
Route::get('tenants/{tenant}/main-page/edit', [TenantController::class, 'editMainPage'])->name('tenants.editMainPage');
Route::post('tenants/{tenant}/main-page', [TenantController::class, 'updateMainPage'])->name('tenants.updateMainPage');

Route::resource('tenants', TenantController::class);

Route::get('forms/{form}/export', [FormController::class, 'export'])->name('forms.export');
Route::resource('forms', FormController::class);
Route::resource('registrations', RegistrationController::class)->only('show');

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
Route::get('settings/atstovavimas', [SettingsController::class, 'editAtstovavimasSettings'])->name('settings.atstovavimas.edit');
Route::post('settings/atstovavimas', [SettingsController::class, 'updateAtstovavimasSettings'])->name('settings.atstovavimas.update');
Route::get('settings/authorization', [SettingsController::class, 'editAuthorization'])->name('settings.authorization.edit');
Route::post('settings/authorization', [SettingsController::class, 'updateAuthorization'])->name('settings.authorization.update');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
