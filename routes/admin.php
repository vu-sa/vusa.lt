<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
Route::get('profile', [DashboardController::class, 'userSettings'])->name('profile');
Route::get('userTasks', [DashboardController::class, 'userTasks'])->name('userTasks');
Route::get('workspace/{institution?}', [DashboardController::class, 'workspace'])->name('workspace');
Route::get('institutionGraph', [DashboardController::class, 'institutionGraph'])->name('institutionGraph');

// Resources
Route::resource('pages', PagesController::class);
Route::post('pages/search', [PagesController::class, 'searchForPage'])->name('pages.search');

Route::resource('news', NewsController::class);
Route::post('news/search', [NewsController::class, 'searchForNews'])->name('news.search');

Route::resource('mainPage', MainPageController::class);
Route::resource('banners', BannerController::class);
Route::resource('navigation', NavigationController::class);
Route::resource('users', UserController::class);
Route::resource('users.comments', CommentController::class);
Route::post('users/{user}/detach/{duty}', [UserController::class, 'detachFromDuty'])->name('users.detach');
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

Route::resource('saziningaiExams', SaziningaiExamsController::class);
Route::resource('saziningaiExamFlows', SaziningaiExamFlowsController::class);
Route::resource('saziningaiExamObservers', SaziningaiExamObserversController::class);
Route::resource('files', FilesController::class);
Route::resource('duties', DutyController::class);
Route::resource('dutiables', DutiableController::class);
Route::resource('institutions', InstitutionController::class);
Route::post('institutions/reorderDuties', [InstitutionController::class, 'reorderDuties'])->name('institutions.reorderDuties');

Route::resource('types', TypeController::class);
Route::resource('relationships', RelationshipController::class);
Route::post('relationships/{relationship}/storeModelRelationship', [RelationshipController::class, 'storeModelRelationship'])->name('relationships.storeModelRelationship');
Route::delete('relationships/relationshipables/{relationshipable}', [RelationshipController::class, 'deleteModelRelationship'])->name('relationships.deleteModelRelationship');
Route::resource('roles', RoleController::class);
Route::patch('roles/{role}/attach/{model}/permissions', [RoleController::class, 'syncPermissionGroup'])->name('roles.syncPermissionGroup');
Route::resource('permissions', PermissionController::class);
Route::resource('tasks', TaskController::class);
Route::post('tasks/{task}/updateCompletionStatus', [TaskController::class, 'updateCompletionStatus'])->name('tasks.updateCompletionStatus');

Route::post('files/search', [FilesController::class, 'searchForFiles'])->name('files.search');
Route::post('images/search', [FilesController::class, 'searchForImages'])->name('images.search');
Route::post('duties/search', [DutyController::class, 'searchForDuties'])->name('duties.search');

Route::post('files/uploadImage', [FilesController::class, 'uploadImage'])->name('files.uploadImage');

Route::get('sharepoint', [SharepointController::class, 'index'])->name('sharepoint.index');
Route::post('sharepoint/addFile', [SharepointController::class, 'addFile'])->name('sharepoint.addFile');
Route::post('sharepoint/getFiles', [SharepointController::class, 'getFilesFromDocumentIds'])->name('sharepoint.getFiles');
Route::delete('sharepoint/{id}', [SharepointController::class, 'destroyFile'])->name('sharepoint.destroy');

Route::post('/logout', [UserController::class, 'logout'])->name('logout');