<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Admin/ShowAdminHome')->name('dashboard');
Route::get('profile', [DashboardController::class, 'userSettings'])->name('profile');
Route::inertia('administration', 'Admin/ShowAdministration')->name('administration');
Route::get('dashboard/atstovavimas', [DashboardController::class, 'atstovavimas'])->name('dashboard.atstovavimas');
Route::get('dashboard/svetaine', [DashboardController::class, 'svetaine'])->name('dashboard.svetaine');
Route::get('dashboard/reservations', [DashboardController::class, 'reservations'])->name('dashboard.reservations');

Route::patch('profile', [DashboardController::class, 'updateUserSettings'])->name('profile.update');
Route::get('userTasks', [DashboardController::class, 'userTasks'])->name('userTasks');
Route::get('institutionGraph', [DashboardController::class, 'institutionGraph'])->name('institutionGraph');

// Restore routes
Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();
Route::patch('pages/{page}/restore', [PageController::class, 'restore'])->name('pages.restore')->withTrashed();
Route::patch('news/{news}/restore', [NewsController::class, 'restore'])->name('news.restore')->withTrashed();
Route::post('news/{news}/duplicate', [NewsController::class, 'duplicate'])->name('news.duplicate');
Route::patch('doings/{doing}/restore', [DoingController::class, 'restore'])->name('doings.restore')->withTrashed();
Route::patch('duties/{duty}/restore', [DutyController::class, 'restore'])->name('duties.restore')->withTrashed();
Route::patch('goals/{goal}/restore', [GoalController::class, 'restore'])->name('goals.restore')->withTrashed();
Route::patch('goalGroups/{goalGroup}/restore', [GoalGroupController::class, 'restore'])->name('goalGroups.restore')->withTrashed();
Route::patch('institutions/{institution}/restore', [InstitutionController::class, 'restore'])->name('institutions.restore')->withTrashed();
Route::patch('matters/{matter}/restore', [MatterController::class, 'restore'])->name('matters.restore')->withTrashed();
Route::patch('meetings/{meeting}/restore', [MeetingController::class, 'restore'])->name('meetings.restore')->withTrashed();
Route::patch('reservations/{reservation}/restore', [ReservationController::class, 'restore'])->name('reservations.restore')->withTrashed();
Route::patch('resources/{resource}/restore', [ResourceController::class, 'restore'])->name('resources.restore')->withTrashed();
Route::patch('types/{type}/restore', [TypeController::class, 'restore'])->name('types.restore')->withTrashed();

// Resources
Route::resource('pages', PageController::class)->except(['show']);
Route::resource('news', NewsController::class)->except(['show']);
Route::resource('categories', CategoryController::class)->except(['show']);

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

Route::post('users/{user}/sendWelcomeEmail', [UserController::class, 'sendWelcomeEmail'])->name('users.sendWelcomeEmail');
Route::get('users/{user}/renderWelcomeEmail', [UserController::class, 'renderWelcomeEmail'])->name('users.renderWelcomeEmail');
Route::resource('users.comments', CommentController::class)->only(['store', 'update', 'destroy']);
Route::post('notification/{id}/markAsRead', [UserNotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
Route::post('notification/markAllAsRead', [UserNotificationsController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

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

Route::resource('calendar', CalendarController::class);
Route::post('calendar/{calendar}/media/{media}', [CalendarController::class, 'destroyMedia'])->name('calendar.destroyMedia');
Route::post('calendar/{calendar}/duplicate', [CalendarController::class, 'duplicate'])->name('calendar.duplicate');

Route::resource('matters', MatterController::class)->except(['edit', 'update']);
Route::resource('goals', GoalController::class);
Route::post('matters/{matter}/attach', [MatterController::class, 'attachGoal'])->name('matters.attachGoal');
Route::resource('goalGroups', GoalGroupController::class)->except(['show']);
Route::resource('doings', DoingController::class);
Route::resource('agendaItems', AgendaItemController::class)->except(['index', 'create']);
Route::resource('meetings', MeetingController::class)->except(['create']);

Route::resource('resources', ResourceController::class);
Route::resource('resourceCategories', ResourceCategoryController::class);

Route::put('reservations/{reservation}/add-users', [ReservationController::class, 'addUsers'])->name('reservations.add-users');
Route::resource('reservations', ReservationController::class);
Route::resource('reservationResources', ReservationResourceController::class)->except(['index', 'create', 'edit']);

Route::get('files/getFiles', [FilesController::class, 'getFiles'])->name('files.getFiles');
Route::post('files/createDirectory', [FilesController::class, 'createDirectory'])->name('files.createDirectory');
Route::delete('files/delete', [FilesController::class, 'delete'])->name('files.delete');
Route::resource('files', FilesController::class);

Route::resource('documents', DocumentController::class)->except('create', 'edit');
Route::post('documents/{document}/refresh', [DocumentController::class, 'refresh'])->name('documents.refresh');

Route::resource('duties', DutyController::class);
Route::resource('dutiables', DutiableController::class)->except(['index', 'show']);
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
Route::delete('relationships/relationshipables/{relationshipable}', [RelationshipController::class, 'deleteModelRelationship'])->name('relationships.deleteModelRelationship');
Route::resource('roles', RoleController::class);
Route::patch('roles/{role}/attach/{model}/permissions', [RoleController::class, 'syncPermissionGroup'])->name('roles.syncPermissionGroup');
Route::put('roles/{role}/sync/duties', [RoleController::class, 'syncDuties'])->name('roles.syncDuties');
Route::put('roles/{role}/sync/attachableTypes', [RoleController::class, 'syncAttachableTypes'])->name('roles.syncAttachableTypes');
Route::resource('permissions', PermissionController::class)->only(['index']);
Route::resource('tasks', TaskController::class)->except(['index', 'create', 'show', 'edit']);
Route::post('tasks/{task}/updateCompletionStatus', [TaskController::class, 'updateCompletionStatus'])->name('tasks.updateCompletionStatus');

Route::resource('changelogItems', ChangelogItemController::class);
Route::post('changelogItems/approveForUser', [ChangelogItemController::class, 'approveForUser'])->name('changelogItems.approve');

Route::post('files/uploadImage', [FilesController::class, 'uploadImage'])->name('files.uploadImage');

Route::resource('sharepointFiles', SharepointFileController::class)->except('create', 'show', 'edit', 'update');

// Route::post('sharepoint/addFile', [SharepointController::class, 'addFile'])->name('sharepoint.addFile');
// Route::post('sharepoint/getFiles', [SharepointController::class, 'getFilesFromDocumentIds'])->name('sharepoint.getFiles');
Route::get('sharepoint/getPotentialFileables', [SharepointFileController::class, 'getPotentialFileables'])->name('sharepoint.getPotentialFileables');
Route::get('sharepoint/getDriveItems', [SharepointFileController::class, 'getDriveItems'])->name('sharepoint.getDriveItems');
Route::get('sharepoint/{id}/permissions', [SharepointFileController::class, 'getDriveItemPublicLink'])->name('sharepoint.getDriveItemPublicLink');
Route::get('sharepoint/{type}/{id}', [SharepointFileController::class, 'getTypesDriveItems'])->name('sharepoint.getTypesDriveItems');
Route::post('sharepoint/{id}/permissions/createPublic', [SharepointFileController::class, 'createPublicPermission'])->name('sharepoint.createPublicPermission');

Route::get('settings/forms', [SettingsController::class, 'editFormSettings'])->name('forms.settings.edit');
Route::post('settings/forms', [SettingsController::class, 'updateFormSettings'])->name('forms.settings.update');

Route::post('/logout', [UserController::class, 'logout'])->name('logout');
