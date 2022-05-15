<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use Laravel\Jetstream\Http\Controllers\Inertia\OtherBrowserSessionsController;
use Laravel\Jetstream\Http\Controllers\Inertia\ProfilePhotoController;
use Laravel\Jetstream\Http\Controllers\Inertia\UserProfileController;
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

Route::prefix('admin')->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {

        // Main
        Route::get('/', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('dashboard');

        // User & Profile...
        Route::get('/user/profile', [UserProfileController::class, 'show'])
            ->name('profile.show');

        Route::delete('/user/other-browser-sessions', [OtherBrowserSessionsController::class, 'destroy'])
            ->name('other-browser-sessions.destroy');

        Route::delete('/user/profile-photo', [ProfilePhotoController::class, 'destroy'])
            ->name('current-user-photo.destroy');

        // Resources
        Route::resource('pages', Admin\PagesController::class);
        Route::post('pages/search', [Admin\PagesController::class, 'searchForPage'])->name('pages.search');

        Route::resource('news', Admin\NewsController::class);
        Route::post('news/search', [Admin\NewsController::class, 'searchForNews'])->name('news.search');

        Route::resource('mainPage', Admin\MainPageController::class);
        Route::resource('banners', Admin\BannerController::class);
        Route::resource('navigation', Admin\NavigationController::class);
        Route::resource('users', Admin\UserController::class);
        Route::resource('calendar', Admin\CalendarController::class);
        Route::resource('saziningaiExams', Admin\SaziningaiExamsController::class);
        Route::resource('saziningaiExamFlows', Admin\SaziningaiExamFlowsController::class);
        Route::resource('saziningaiExamObservers', Admin\SaziningaiExamObserversController::class);
        Route::resource('files', Admin\FilesController::class);
        Route::resource('duties', Admin\DutyController::class);
        Route::resource('dutyInstitutions', Admin\DutyInstitutionController::class);
        Route::post('dutyInstitutions/search', [Admin\DutyInstitutionController::class, 'searchForInstitutions'])->name('dutyInstitutions.search');
        
        Route::resource('roles', Admin\RolesController::class);
        Route::post('files/search', [Admin\FilesController::class, 'searchForFiles'])->name('files.search');
        Route::post('images/search', [Admin\FilesController::class, 'searchForImages'])->name('images.search');
        Route::post('duties/search', [Admin\DutyController::class, 'searchForDuties'])->name('duties.search');

        Route::post('files/uploadImage', [Admin\FilesController::class, 'uploadImage'])->name('files.uploadImage');
    });
});

Route::get('/auth/redirect', function () {
    return Socialite::driver('microsoft')->redirect();
})->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [Admin\UserController::class, 'storeFromMicrosoft'])->name('microsoft.callback');
// $microsoftUser = Socialite::driver('microsoft')->user();
// return redirect('https://graph.microsoft.com/v1.0/me/photo/$value');
// });

Route::group(['prefix' => '{lang?}', 'where' => ['lang' => '(lt|en)']], function () {
    
    Route::name('padalinys.')->group(function () {
        Route::domain('{padalinys}.' . $this->host)->group(function () {
            Route::get('/', [Public\MainController::class, 'home'])->name('home');
            Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('newsString', '(naujiena|news)')->name('news');
            Route::get('kontaktai', [Public\MainController::class, 'contacts'])->name('contacts');
            Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');
        });
    });

    Route::name('main.')->group(function () {
        Route::domain($this->host)->group(function () {
            Route::get('/', [Public\MainController::class, 'home'])->name('home');
            Route::get('naujienos', [Public\MainController::class, 'newsArchive'])->name('newsArchive');
            Route::get('naujiena/archyvas', [Public\MainController::class, 'newsArchive']);
            Route::get('saziningai-registracija', [Public\MainController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
            Route::get('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'saziningaiExams'])->name('saziningaiExams.registered');
            Route::get('ataskaita-2022', [Public\MainController::class, 'ataskaita2022']);
            Route::get('ataskaita-2022/{permalink}', [Public\MainController::class, 'ataskaita2022'])->where('permalink', '.*')->name('ataskaita2022');
            Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('news_string', '(naujiena|news)')->name('news');
            Route::get('mainNews', [Public\MainController::class, 'getMainNews']);
            
            Route::get('kontaktai', [Public\MainController::class, 'contacts'])->name('contacts');
            Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');
        });
    });
    
});

Route::get('/', [Public\MainController::class, 'home'])->name('home');
Route::get('naujienos', [Public\MainController::class, 'newsArchive'])->name('newsArchive');
// redirect /naujiena/archyvas to newsArchive
Route::get('naujiena/archyvas', [Public\MainController::class, 'newsArchive']);
Route::get('saziningai-registracija', [Public\MainController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
Route::post('saziningai-registracija', [Public\MainController::class, 'storeSaziningaiExamRegistration'])->name('saziningaiExamRegistration.store');
Route::get('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'saziningaiExams'])->name('saziningaiExams.registered');
Route::post('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'storeSaziningaiExamObserver'])->name('saziningaiExamObserver.store');
Route::get('ataskaita-2022', [Public\MainController::class, 'ataskaita2022']);
Route::get('ataskaita-2022/{permalink}', [Public\MainController::class, 'ataskaita2022'])->where('permalink', '.*')->name('ataskaita2022');
Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('newsString', '(naujiena|news)')->name('news');

Route::get('kontaktai', [Public\MainController::class, 'contacts'])->name('contacts');

Route::post('search', [Public\MainController::class, 'search'])->name('search');

Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');

/**
 * Statiniai seni tinklapio routai
 */

// if (in_array($http_host, $padaliniaiDomains)) {
//     Route::get('{locale}', [UserController::class, 'getPadalinysPage'])->where('locale', '(lt|en)');
//     Route::get('lt/naujienos', [UserController::class, 'getNewsArchive']);
//     Route::get('lt/naujiena/archyvas', [UserController::class, 'getNewsArchive']);

//     Route::get('{locale}/{contactsLocale}/{name}', [UserController::class, 'getContacts'])->where(['locale' => '(lt|en)', 'contactsLocale' => '(kontaktai|contacts)']);
//     Route::get('{locale}/{permalink}', [UserController::class, 'getInfoPage'])->where('locale', '(lt|en)');
//     Route::get('/{permalink}', [UserController::class, 'index'])->middleware('main');
// }

// if (in_array($http_host, $vusaDomains)) {
//     Route::get('/', [UserController::class, 'index'])->middleware('main');
//     Route::get('{locale}', [UserController::class, 'index'])->where('locale', '(lt|en)');

//     // Grąžina JSON pavidalu 4 svarbiausias naujienas. Turbūt naudojamas ekranai.vusa.lt puslapiui
//     Route::get('lt/mainNews', [UserController::class, 'getMainNews']);
//     Route::post('lt/paieska', [UserController::class, 'getMainPageSearch']);
//     Route::get('lt/naujiena/archyvas', [UserController::class, 'getNewsArchive']);
//     Route::post('lt/naujiena/archyvas', [UserController::class, 'postNewsArchive']);
//     Route::get('lt/renginiai', [UserController::class, 'getEvents']);
//     Route::get('lt/darbotvarke', [UserController::class, 'getAgenda']);
//     Route::get('lt/darbotvarke-ajax', [UserController::class, 'getAgendaAjax']);

//     Route::get('lt/saziningai-registracija', [UserController::class, 'getExamRegistration']);
//     Route::post('lt/saziningai-registracija', [UserController::class, 'postExamRegistration']);
//     Route::get('lt/registracija-stebejimui', [UserController::class, 'getPersonRegistration']);
//     Route::post('lt/registracija-stebejimui', [UserController::class, 'postPersonRegistration']);
//     Route::get('lt/saziningai-uzregistruoti-egzaminai', [UserController::class, 'getExamRegistrationList']);

// Route::get('{locale}/{contactsLocale}/{name}', [UserController::class, 'getContacts'])->where(['locale' => '(lt|en)', 'contactsLocale' => '(kontaktai|contacts)']);

// Route::get('en/news/tag/{tag}', [UserController::class, 'getSearchByTag']);
// // Route::get('en/news/{title}', [UserController::class, 'getNew']);

// /**
//  * Dinaminiai tinklapio routai
//  */

// // Route::get('lt/kontaktai/{name}', [UserController::class, 'getContacts']);
// Route::get('lt/naujiena/zyme/{tag}', [UserController::class, 'getSearchByTag']);
// Route::get('{locale}/{newsLocale}/{permalink}', [UserController::class, 'getNew'])->where(['locale' => '(lt|en)', 'newsLocale' => '(naujiena|news)']);

// // Catch all

// Route::get('{locale}/{permalink}', [UserController::class, 'getInfoPage'])->where('locale', '(lt|en)');

// Route::get('/{permalink}', [UserController::class, 'getInfoPage'])->where('permalink', '.*')->middleware('main');
