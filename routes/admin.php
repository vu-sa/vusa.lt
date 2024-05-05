<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
Route::get('profile', [DashboardController::class, 'userSettings'])->name('profile');
Route::get('userTasks', [DashboardController::class, 'userTasks'])->name('userTasks');
Route::get('workspace', [DashboardController::class, 'workspace'])->name('workspace');
Route::get('institutionGraph', [DashboardController::class, 'institutionGraph'])->name('institutionGraph');
Route::get('stats/representatives', [StatsController::class, 'representativesInPadalinys'])->name('stats.representativesInPadalinys');

Route::post('sendFeedback', [DashboardController::class, 'sendFeedback'])->name('sendFeedback');

// Restore routes
Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();
Route::patch('pages/{page}/restore', [PageController::class, 'restore'])->name('pages.restore')->withTrashed();
Route::patch('news/{news}/restore', [NewsController::class, 'restore'])->name('news.restore')->withTrashed();
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

// change order main page
Route::get('mainPage/padalinys/{padalinys}/edit-order/{lang}', [MainPageController::class, 'editOrder'])->name('mainPage.edit-order')
    ->whereIn('lang', ['lt', 'en']);

Route::post('mainPage/update-order', [MainPageController::class, 'updateOrder'])->name('mainPage.update-order');
Route::resource('mainPage', MainPageController::class)->except(['show']);
Route::resource('banners', BannerController::class)->except(['show']);
Route::resource('navigation', NavigationController::class)->except(['show']);
/*Route::get('navigation/editAll', [NavigationController::class, 'editAll'])->name('navigation.editAll');*/
Route::post('navigation/updateOrder', [NavigationController::class, 'updateOrder'])->name('navigation.updateOrder');
Route::post('navigation/updateColumn', [NavigationController::class, 'updateColumn'])->name('navigation.updateColumn');
Route::resource('users', UserController::class);

Route::post('users/{user}/sendWelcomeEmail', [UserController::class, 'sendWelcomeEmail'])->name('users.sendWelcomeEmail');
Route::get('users/{user}/renderWelcomeEmail', [UserController::class, 'renderWelcomeEmail'])->name('users.renderWelcomeEmail');
Route::resource('users.comments', CommentController::class)->only(['store', 'update', 'destroy']);
Route::post('notification/{id}/markAsRead', [UserNotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
Route::post('notification/markAllAsRead', [UserNotificationsController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

Route::resource('contacts', ContactController::class);

Route::resource('calendar', CalendarController::class);
Route::post('calendar/{calendar}/media/{media}', [CalendarController::class, 'destroyMedia'])->name('calendar.destroyMedia');
Route::resource('registrationForms', RegistrationFormController::class)->only(['store', 'show', 'index']);

Route::resource('matters', MatterController::class)->except(['edit', 'update']);
Route::resource('goals', GoalController::class);
Route::post('matters/{matter}/attach', [MatterController::class, 'attachGoal'])->name('matters.attachGoal');
Route::resource('goalGroups', GoalGroupController::class)->except(['show']);
Route::resource('doings', DoingController::class);
Route::resource('agendaItems', AgendaItemController::class)->except(['index', 'create']);
Route::resource('meetings', MeetingController::class);

Route::resource('resources', ResourceController::class);
Route::put('reservations/{reservation}/add-users', [ReservationController::class, 'addUsers'])->name('reservations.add-users');
Route::resource('reservations', ReservationController::class);
Route::resource('reservationResources', ReservationResourceController::class);

Route::resource('saziningaiExams', SaziningaiExamsController::class);
Route::resource('saziningaiExamFlows', SaziningaiExamFlowsController::class)->except(['index', 'create', 'show', 'destroy']);
Route::get('files/getFiles', [FilesController::class, 'getFiles'])->name('files.getFiles');
Route::post('files/createDirectory', [FilesController::class, 'createDirectory'])->name('files.createDirectory');
Route::delete('files/delete', [FilesController::class, 'delete'])->name('files.delete');
Route::resource('files', FilesController::class);

Route::put('duties/setAsStudentRepresentatives', [DutyController::class, 'setAsStudentRepresentatives'])->name('duties.setAsStudentRepresentatives');
Route::resource('duties', DutyController::class);
Route::resource('dutiables', DutiableController::class);
Route::post('institutions/reorderDuties', [InstitutionController::class, 'reorderDuties'])->name('institutions.reorderDuties');
Route::resource('institutions', InstitutionController::class);

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

Route::post('duties/search', [DutyController::class, 'searchForDuties'])->name('duties.search');

Route::post('files/uploadImage', [FilesController::class, 'uploadImage'])->name('files.uploadImage');

Route::resource('sharepointFiles', SharepointFileController::class)->except('create', 'show', 'edit', 'update');

// Route::post('sharepoint/addFile', [SharepointController::class, 'addFile'])->name('sharepoint.addFile');
// Route::post('sharepoint/getFiles', [SharepointController::class, 'getFilesFromDocumentIds'])->name('sharepoint.getFiles');
// Route::delete('sharepoint/{id}', [SharepointController::class, 'destroyFile'])->name('sharepoint.destroy');
Route::get('sharepoint/getPotentialFileables', [SharepointFileController::class, 'getPotentialFileables'])->name('sharepoint.getPotentialFileables');
Route::get('sharepoint/getDriveItems', [SharepointFileController::class, 'getDriveItems'])->name('sharepoint.getDriveItems');
Route::get('sharepoint/{id}/permissions', [SharepointFileController::class, 'getDriveItemPermissions'])->name('sharepoint.getDriveItemPermissions');
Route::get('sharepoint/{type}/{id}', [SharepointFileController::class, 'getTypesDriveItems'])->name('sharepoint.getTypesDriveItems');
Route::post('sharepoint/{id}/permissions/createPublic', [SharepointFileController::class, 'createPublicPermission'])->name('sharepoint.createPublicPermission');

Route::post('/logout', [UserController::class, 'logout'])->name('logout');
