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

// Resources
Route::resource('pages', PageController::class);
Route::post('pages/search', [PageController::class, 'searchForPage'])->name('pages.search');

Route::resource('news', NewsController::class);
Route::post('news/search', [NewsController::class, 'searchForNews'])->name('news.search');

Route::resource('mainPage', MainPageController::class);
Route::resource('banners', BannerController::class);
Route::resource('navigation', NavigationController::class);
Route::resource('users', UserController::class);
Route::post('users/{user}/sendWelcomeEmail', [UserController::class, 'sendWelcomeEmail'])->name('users.sendWelcomeEmail');
Route::get('users/{user}/renderWelcomeEmail', [UserController::class, 'renderWelcomeEmail'])->name('users.renderWelcomeEmail');
Route::resource('users.comments', CommentController::class)->only(['store', 'destroy']);
Route::post('notification/{id}/markAsRead', [UserNotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
Route::post('notification/markAllAsRead', [UserNotificationsController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

Route::resource('contacts', ContactController::class);

Route::resource('calendar', CalendarController::class);
Route::post('calendar/{calendar}/media/{media}', [CalendarController::class, 'destroyMedia'])->name('calendar.destroyMedia');
Route::resource('registrationForms', RegistrationFormController::class);
Route::resource('registrations', RegistrationController::class);

Route::resource('matters', MatterController::class);
Route::resource('goals', GoalController::class);
Route::post('matters/{matter}/attach', [MatterController::class, 'attachGoal'])->name('matters.attachGoal');
Route::resource('goalGroups', GoalGroupController::class);
Route::resource('doings', DoingController::class);
Route::resource('agendaItems', AgendaItemController::class);
Route::resource('meetings', MeetingController::class);

Route::resource('resources', ResourceController::class);
Route::put('reservations/{reservation}/add-users', [ReservationController::class, 'addUsers'])->name('reservations.add-users');
Route::resource('reservations', ReservationController::class);
Route::resource('reservationResources', ReservationResourceController::class);

Route::resource('saziningaiExams', SaziningaiExamsController::class);
Route::resource('saziningaiExamFlows', SaziningaiExamFlowsController::class);
Route::resource('saziningaiExamObservers', SaziningaiExamObserversController::class);
Route::resource('files', FilesController::class);

Route::put('duties/setAsStudentRepresentatives', [DutyController::class, 'setAsStudentRepresentatives'])->name('duties.setAsStudentRepresentatives');
Route::resource('duties', DutyController::class);
Route::resource('duties.users', DutiableController::class);
Route::post('institutions/reorderDuties', [InstitutionController::class, 'reorderDuties'])->name('institutions.reorderDuties');
Route::resource('institutions', InstitutionController::class);

Route::resource('types', TypeController::class);
Route::resource('relationships', RelationshipController::class);
Route::post('relationships/{relationship}/storeModelRelationship', [RelationshipController::class, 'storeModelRelationship'])->name('relationships.storeModelRelationship');
Route::delete('relationships/relationshipables/{relationshipable}', [RelationshipController::class, 'deleteModelRelationship'])->name('relationships.deleteModelRelationship');
Route::resource('roles', RoleController::class);
Route::patch('roles/{role}/attach/{model}/permissions', [RoleController::class, 'syncPermissionGroup'])->name('roles.syncPermissionGroup');
Route::put('roles/{role}/sync/duties', [RoleController::class, 'syncDuties'])->name('roles.syncDuties');
Route::resource('permissions', PermissionController::class);
Route::resource('tasks', TaskController::class);
Route::post('tasks/{task}/updateCompletionStatus', [TaskController::class, 'updateCompletionStatus'])->name('tasks.updateCompletionStatus');

Route::resource('changelogItems', ChangelogItemController::class);
Route::post('changelogItems/approveForUser', [ChangelogItemController::class, 'approveForUser'])->name('changelogItems.approve');

Route::post('files/search', [FilesController::class, 'searchForFiles'])->name('files.search');
Route::post('images/search', [FilesController::class, 'searchForImages'])->name('images.search');
Route::post('duties/search', [DutyController::class, 'searchForDuties'])->name('duties.search');

Route::post('files/uploadImage', [FilesController::class, 'uploadImage'])->name('files.uploadImage');

Route::resource('sharepointFiles', SharepointFileController::class);

// Route::post('sharepoint/addFile', [SharepointController::class, 'addFile'])->name('sharepoint.addFile');
// Route::post('sharepoint/getFiles', [SharepointController::class, 'getFilesFromDocumentIds'])->name('sharepoint.getFiles');
// Route::delete('sharepoint/{id}', [SharepointController::class, 'destroyFile'])->name('sharepoint.destroy');
Route::get('sharepoint/getPotentialFileables', [SharepointFileController::class, 'getPotentialFileables'])->name('sharepoint.getPotentialFileables');
Route::get('sharepoint/getDriveItems', [SharepointFileController::class, 'getDriveItems'])->name('sharepoint.getDriveItems');
Route::get('sharepoint/{id}/permissions', [SharepointFileController::class, 'getDriveItemPermissions'])->name('sharepoint.getDriveItemPermissions');
Route::get('sharepoint/{type}/{id}', [SharepointFileController::class, 'getTypesDriveItems'])->name('sharepoint.getTypesDriveItems');
Route::post('sharepoint/{id}/permissions/createPublic', [SharepointFileController::class, 'createPublicPermission'])->name('sharepoint.createPublicPermission');

Route::post('/logout', [UserController::class, 'logout'])->name('logout');
