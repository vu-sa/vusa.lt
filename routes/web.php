<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

$this->host = config('app.url');
$this->host = explode('://', $this->host)[1];

Route::feeds();

Route::prefix('admin')->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {

        // TODO: use a namespace group

        // Main
        Route::get('/', [Admin\DashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('profile', [Admin\DashboardController::class, 'userSettings'])->name('profile');
        Route::get('userTasks', [Admin\DashboardController::class, 'userTasks'])->name('userTasks');
        Route::get('workspace/{institution?}', [Admin\DashboardController::class, 'workspace'])->name('workspace');
        Route::get('institutionGraph', [Admin\DashboardController::class, 'institutionGraph'])->name('institutionGraph');

        // Resources
        Route::resource('pages', Admin\PagesController::class);
        Route::post('pages/search', [Admin\PagesController::class, 'searchForPage'])->name('pages.search');

        Route::resource('news', Admin\NewsController::class);
        Route::post('news/search', [Admin\NewsController::class, 'searchForNews'])->name('news.search');

        Route::resource('mainPage', Admin\MainPageController::class);
        Route::resource('banners', Admin\BannerController::class);
        Route::resource('navigation', Admin\NavigationController::class);
        Route::resource('users', Admin\UserController::class);
        Route::resource('users.comments', Admin\CommentController::class);
        Route::post('users/{user}/detach/{duty}', [Admin\UserController::class, 'detachFromDuty'])->name('users.detach');
        Route::post('notification/{id}/markAsRead', [Admin\UserNotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');

        Route::resource('contacts', Admin\ContactController::class);

        Route::resource('calendar', Admin\CalendarController::class);
        Route::post('calendar/{calendar}/media/{media}', [Admin\CalendarController::class, 'destroyMedia'])->name('calendar.destroyMedia');
        Route::resource('registrationForms', Admin\RegistrationFormController::class);
        Route::resource('registrations', Admin\RegistrationController::class);

        Route::resource('matters', Admin\MatterController::class);
        Route::resource('goals', Admin\GoalController::class);
        Route::resource('goalGroups', Admin\GoalGroupController::class);
        Route::post('matters/{matter}/attach/{goal}', [Admin\MatterController::class, 'attachGoal'])->name('matters.attachGoal');
        Route::resource('doings', Admin\DoingController::class);
        Route::resource('meetings', Admin\MeetingController::class);

        Route::resource('saziningaiExams', Admin\SaziningaiExamsController::class);
        Route::resource('saziningaiExamFlows', Admin\SaziningaiExamFlowsController::class);
        Route::resource('saziningaiExamObservers', Admin\SaziningaiExamObserversController::class);
        Route::resource('files', Admin\FilesController::class);
        Route::resource('duties', Admin\DutyController::class);
        Route::resource('dutiables', Admin\DutiableController::class);
        Route::resource('institutions', Admin\InstitutionController::class);
        Route::post('institutions/reorderDuties', [Admin\InstitutionController::class, 'reorderDuties'])->name('institutions.reorderDuties');

        Route::resource('types', Admin\TypeController::class);
        Route::resource('relationships', Admin\RelationshipController::class);
        Route::post('relationships/{relationship}/storeModelRelationship', [Admin\RelationshipController::class, 'storeModelRelationship'])->name('relationships.storeModelRelationship');
        Route::delete('relationships/relationshipables/{relationshipable}', [Admin\RelationshipController::class, 'deleteModelRelationship'])->name('relationships.deleteModelRelationship');
        Route::resource('roles', Admin\RoleController::class);
        Route::patch('roles/{role}/attach/{model}/permissions', [Admin\RoleController::class, 'syncPermissionGroup'])->name('roles.syncPermissionGroup');
        Route::resource('permissions', Admin\PermissionController::class);
        Route::resource('tasks', Admin\TaskController::class);
        Route::post('tasks/{task}/updateCompletionStatus', [Admin\TaskController::class, 'updateCompletionStatus'])->name('tasks.updateCompletionStatus');

        Route::post('files/search', [Admin\FilesController::class, 'searchForFiles'])->name('files.search');
        Route::post('images/search', [Admin\FilesController::class, 'searchForImages'])->name('images.search');
        Route::post('duties/search', [Admin\DutyController::class, 'searchForDuties'])->name('duties.search');

        Route::post('files/uploadImage', [Admin\FilesController::class, 'uploadImage'])->name('files.uploadImage');

        Route::get('sharepoint', [Admin\SharepointController::class, 'index'])->name('sharepoint.index');
        Route::post('sharepoint/addFile', [Admin\SharepointController::class, 'addFile'])->name('sharepoint.addFile');
        Route::post('sharepoint/getFiles', [Admin\SharepointController::class, 'getFilesFromDocumentIds'])->name('sharepoint.getFiles');
        Route::delete('sharepoint/{id}', [Admin\SharepointController::class, 'destroyFile'])->name('sharepoint.destroy');

        Route::post('/logout', [Admin\UserController::class, 'logout'])->name('logout');
    });
});

Route::get('/auth/redirect', function () {
    return Socialite::driver('microsoft')->redirect();
})->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [Admin\UserController::class, 'storeFromMicrosoft'])->name('microsoft.callback');

Route::group(['prefix' => '{lang?}', 'where' => ['lang' => '(lt|en)']], function () {

    Route::name('padalinys.')->group(function () {
        Route::domain('{padalinys}.' . $this->host)->group(function () {
            Route::get('/', [Public\MainController::class, 'home'])->name('home');
            Route::get('naujienos', [Public\MainController::class, 'newsArchive'])->name('newsArchive');
            Route::get('naujiena/archyvas', [Public\MainController::class, 'newsArchive']);
            Route::get('saziningai-registracija', [Public\MainController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
            Route::post('saziningai-registracija', [Public\MainController::class, 'storeSaziningaiExamRegistration'])->name('saziningaiExamRegistration.store');
            Route::get('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'saziningaiExams'])->name('saziningaiExams.registered');
            Route::post('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'storeSaziningaiExamObserver'])->name('saziningaiExamObserver.store');

            Route::get('ataskaita-2022', [Public\MainController::class, 'ataskaita2022']);
            Route::get('ataskaita-2022/{permalink}', [Public\MainController::class, 'ataskaita2022'])->where('permalink', '.*')->name('ataskaita2022');

            Route::get('kontaktai', [Public\MainController::class, 'contactsCategory'])->name('contacts');
            Route::get('kontaktai/paieska', [Public\MainController::class, 'searchContacts'])->name('contacts.search');
            Route::get('kontaktai/kategorija/{alias}', [Public\MainController::class, 'contactsCategory'])->name('contacts.category');
            Route::get('kontaktai/{alias}', [Public\MainController::class, 'contacts'])->name('contacts.alias');

            Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('news_string', '(naujiena|news)')->name('news');

            Route::post('search', [Public\MainController::class, 'search'])->name('search');

            Route::get('mainNews', [Public\MainController::class, 'getMainNews']);
            Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');
        });
    });

    Route::name('main.')->group(function () {
        Route::domain($this->host)->group(function () {
            Route::get('/', [Public\MainController::class, 'home'])->name('home');
            Route::get('naujienos', [Public\MainController::class, 'newsArchive'])->name('newsArchive');
            Route::get('naujiena/archyvas', [Public\MainController::class, 'newsArchive']);
            Route::get('saziningai-registracija', [Public\MainController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
            Route::post('saziningai-registracija', [Public\MainController::class, 'storeSaziningaiExamRegistration'])->name('saziningaiExamRegistration.store');
            Route::get('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'saziningaiExams'])->name('saziningaiExams.registered');
            Route::post('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'storeSaziningaiExamObserver'])->name('saziningaiExamObserver.store');
            
            Route::get('nariu-registracija', [Public\MainController::class, 'memberRegistration'])->name('memberRegistration');
            Route::post('nariu-registracija', [Public\MainController::class, 'storeMemberRegistration'])->name('memberRegistration.store');

            Route::get('ataskaita-2022', [Public\MainController::class, 'ataskaita2022']);
            Route::get('ataskaita-2022/{permalink}', [Public\MainController::class, 'ataskaita2022'])->where('permalink', '.*')->name('ataskaita2022');

            Route::get('kontaktai', [Public\MainController::class, 'contactsCategory'])->name('contacts');
            Route::get('kontaktai/paieska', [Public\MainController::class, 'searchContacts'])->name('contacts.search');
            Route::get('kontaktai/kategorija/{alias}', [Public\MainController::class, 'contactsCategory'])->name('contacts.category');
            Route::get('kontaktai/{alias}', [Public\MainController::class, 'contacts'])->name('contacts.alias');

            Route::get('kalendorius/ics', [Public\MainController::class, 'publicAllEventCalendar'])->name('calendar.ics');

            // because of this, can't get current route for main.page, it shows main.news
            Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('news_string', '(naujiena|news)')->name('news');

            // Route::middleware(['throttle:summerCamps'])->group(function () {
                Route::get('pirmakursiu-stovyklos', [Public\MainController::class, 'summerCamps'])->name('pirmakursiuStovyklos');
                Route::get('kalendorius/renginys/{calendar}', [Public\MainController::class, 'calendarEventMain'])->name('calendar.event');
            // });

            Route::post('search', [Public\MainController::class, 'search'])->name('search');

            Route::get('mainNews', [Public\MainController::class, 'getMainNews']);

            Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');
        });
    });
});

Route::get('/', [Public\MainController::class, 'home'])->name('home');
Route::get('naujienos', [Public\MainController::class, 'newsArchive'])->name('newsArchive');

Route::get('naujiena/archyvas', [Public\MainController::class, 'newsArchive']);

// render login form
Route::inertia('login', 'LoginForm')->middleware('guest')->name('login');
Route::post('login', [Admin\UserController::class, 'authenticate'])->middleware('guest');

Route::get('saziningai-registracija', [Public\MainController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
Route::post('saziningai-registracija', [Public\MainController::class, 'storeSaziningaiExamRegistration'])->name('saziningaiExamRegistration.store');
Route::get('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'saziningaiExams'])->name('saziningaiExams.registered');
Route::post('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'storeSaziningaiExamObserver'])->name('saziningaiExamObserver.store');

Route::get('nariu-registracija', [Public\MainController::class, 'memberRegistration'])->name('memberRegistration');

Route::get('ataskaita-2022', [Public\MainController::class, 'ataskaita2022']);
Route::get('ataskaita-2022/{permalink}', [Public\MainController::class, 'ataskaita2022'])->where('permalink', '.*')->name('ataskaita2022');
Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('newsString', '(naujiena|news)')->name('news');

Route::get('kontaktai', [Public\MainController::class, 'contactsCategory'])->name('contacts');
Route::get('kontaktai/paieska', [Public\MainController::class, 'searchContacts'])->name('contacts.search');
Route::get('kontaktai/kategorija/{alias}', [Public\MainController::class, 'contactsCategory'])->name('contacts.category');
Route::get('kontaktai/{alias}', [Public\MainController::class, 'contacts'])->name('contacts.alias');

Route::get('pirmakursiu-stovyklos', [Public\MainController::class, 'summerCamps'])->name('pirmakursiuStovyklos');
Route::get('kalendorius/renginys/{calendar}', [Public\MainController::class, 'calendarEvent'])->name('calendar.event');

Route::middleware(['throttle:formRegistrations'])->group(function () {
    Route::post('registration/{registrationForm}', [Public\MainController::class, 'storeRegistration'])->name('registration.store');
});

Route::post('search', [Public\MainController::class, 'search'])->name('search');

Route::get('mainNews', [Public\MainController::class, 'getMainNews']);

Route::get('kalendorius/ics', [Public\MainController::class, 'publicAllEventCalendar'])->name('calendar.ics');

Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');
