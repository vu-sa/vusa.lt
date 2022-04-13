<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
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
        Route::resource('news', Admin\NewsController::class);
        Route::resource('mainPage', Admin\MainPageController::class);
        Route::resource('banners', Admin\BannerController::class);
        Route::resource('navigation', Admin\NavigationController::class);
        Route::resource('users', Admin\UserController::class);
        Route::resource('calendar', Admin\CalendarController::class);
        Route::resource('saziningaiExams', Admin\SaziningaiExamsController::class);
        Route::resource('saziningaiExamFlows', Admin\SaziningaiExamFlowsController::class);
        Route::resource('saziningaiExamObservers', Admin\SaziningaiExamObserversController::class);
        Route::resource('files', Admin\FilesController::class);
    });
});

Route::get('/auth/redirect', function () {
    return Socialite::driver('microsoft')->redirect();
})->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [Admin\UserController::class, 'storeFromMicrosoft'])->name('microsoft.callback');
// $microsoftUser = Socialite::driver('microsoft')->user();
// return redirect('https://graph.microsoft.com/v1.0/me/photo/$value');
// });

Route::get('/', [Public\MainController::class, 'home'])->name('home');

/**
 * Statiniai seni tinklapio routai
 */

$http_host = request()->getHttpHost();

$padaliniaiDomains = array(
    'www.chgf.vusa.lt', 'chgf.vusa.lt', 'evaf.vusa.lt', 'www.evaf.vusa.lt', 'ff.vusa.lt', 'www.ff.vusa.lt', 'filf.vusa.lt', 'www.filf.vusa.lt', 'fsf.vusa.lt',
    'www.fsf.vusa.lt', 'gmc.vusa.lt', 'www.gmc.vusa.lt', 'naujas.if.vusa.lt', 'if.vusa.lt', 'www.if.vusa.lt', 'kf.vusa.lt', 'www.kf.vusa.lt', 'knf.vusa.lt', 'www.knf.vusa.lt', 'knfsa.lt', 'www.knfsa.lt', 'mf.vusa.lt', 'www.mf.vusa.lt',
    'mif.vusa.lt', 'www.sa.vusa.lt', 'sa.vusa.lt', 'www.mif.vusa.lt', 'tf.vusa.lt', 'www.tf.vusa.lt', 'tspmi.vusa.lt', 'www.tspmi.vusa.lt', 'vm.vusa.lt', 'www.vm.vusa.lt', 'if.vusa.testas:8000', 'if.vusa.testas'
);

$vusaDomains = array('vusa.lt', 'www.vusa.lt', 'naujas.vusa.lt', 'vusa.testas:8000', 'vusa.testas');

if (in_array($http_host, $padaliniaiDomains)) {
    Route::get('{locale}', [UserController::class, 'getPadalinysPage'])->where('locale', '(lt|en)');
    Route::get('lt/naujienos', [UserController::class, 'getNewsArchive']);
    Route::get('lt/naujiena/archyvas', [UserController::class, 'getNewsArchive']);

    // This must be kept as '{permalink1}', if named '{permalink}', it skips this route and continues towards the end (maybe a bug). Basically, try to use unique names.
    Route::get('{locale}/{newsLocale}/{permalink1}', [UserController::class, 'getNew'])->where(['locale' => '(lt|en)', 'newsLocale' => '(naujiena|news)']);

    Route::get('{locale}/{contactsLocale}/{name}', [UserController::class, 'getContacts'])->where(['locale' => '(lt|en)', 'contactsLocale' => '(kontaktai|contacts)']);
    Route::get('{locale}/{permalink}', [UserController::class, 'getInfoPage'])->where('locale', '(lt|en)');
    Route::get('/{permalink}', [UserController::class, 'index'])->middleware('main');
}

if (in_array($http_host, $vusaDomains)) {
    Route::get('/', [UserController::class, 'index'])->middleware('main');
    Route::get('{locale}', [UserController::class, 'index'])->where('locale', '(lt|en)');

    // Grąžina JSON pavidalu 4 svarbiausias naujienas. Turbūt naudojamas ekranai.vusa.lt puslapiui
    Route::get('lt/mainNews', [UserController::class, 'getMainNews']);
    Route::post('lt/paieska', [UserController::class, 'getMainPageSearch']);
    Route::get('lt/naujiena/archyvas', [UserController::class, 'getNewsArchive']);
    Route::post('lt/naujiena/archyvas', [UserController::class, 'postNewsArchive']);
    Route::get('lt/renginiai', [UserController::class, 'getEvents']);
    Route::get('lt/darbotvarke', [UserController::class, 'getAgenda']);
    Route::get('lt/darbotvarke-ajax', [UserController::class, 'getAgendaAjax']);
    Route::get('lt/duk', [UserController::class, 'getDukPage']);
    Route::get('lt/programos-klubai-projektai', [UserController::class, 'getPkpPage']);
    Route::get('lt/puslapis-nerastas', [UserController::class, 'page404']);

    Route::get('lt/registracija-i-poilsio-namus', [UserController::class, 'getRestRegistration']);
    Route::post('lt/registracija-i-poilsio-namus', [UserController::class, 'postRestRegistration']);
    Route::get('lt/registracija-i-poilsio-namus-diena', [UserController::class, 'getRestRegistrationDays']);

    Route::get('lt/saziningai-registracija', [UserController::class, 'getExamRegistration']);
    Route::post('lt/saziningai-registracija', [UserController::class, 'postExamRegistration']);
    Route::get('lt/registracija-stebejimui', [UserController::class, 'getPersonRegistration']);
    Route::post('lt/registracija-stebejimui', [UserController::class, 'postPersonRegistration']);
    Route::get('lt/saziningai-uzregistruoti-egzaminai', [UserController::class, 'getExamRegistrationList']);
    Route::get('lt/saziningai-egzaminai-ajax', [UserController::class, 'getExamRegistrationAjax']);

    /***
     * Redirectai
     */
    Route::get('lt/kontaktai', function () {
        return redirect('/lt/kontaktai/centrinis-biuras');
    });

    Route::get('{locale}/contacts', function () {
        return redirect('/en/contacts/central-office');
    })->where('locale', '(|lt|en)');

    Route::get('/lt/studentu-atstovai', function () {
        return redirect('/lt/kontaktai/studentu-atstovai');
    });

    Route::get('/lt/vu-sa-padaliniai', function () {
        return redirect('/lt/kontaktai/padaliniai');
    });

    Route::get('/lt/d-u-k', function () {
        return redirect('/lt/duk');
    });

    Route::get('/lt/vu-sa-revizijos-komisija', function () {
        return redirect('/lt/kontaktai/revizijos-komisija');
    });

    Route::get('{locale}/vu-atributika', function () {
        return redirect('https://vu.lt/parduotuve');
    })->where('locale', '(|lt|en)');

    Route::get('{locale}/pasiskiepyk', function () {
        return redirect('https://koronastop.lrv.lt/lt/vakcina');
    })->where('locale', '(|lt|en)');

    Route::get('/lt/{url}', function () {
        return redirect('login');
    })->where('url', '(login|admin)');
}

Route::get('en/scholarship/{title}', [UserController::class, 'page']);

Route::get('{locale}/{contactsLocale}/{name}', [UserController::class, 'getContacts'])->where(['locale' => '(lt|en)', 'contactsLocale' => '(kontaktai|contacts)']);

Route::get('en/news/tag/{tag}', [UserController::class, 'getSearchByTag']);
// Route::get('en/news/{title}', [UserController::class, 'getNew']);

/**
 * Dinaminiai tinklapio routai
 */

// Route::get('lt/kontaktai/{name}', [UserController::class, 'getContacts']);
Route::get('lt/naujiena/zyme/{tag}', [UserController::class, 'getSearchByTag']);
Route::get('{locale}/{newsLocale}/{permalink}', [UserController::class, 'getNew'])->where(['locale' => '(lt|en)', 'newsLocale' => '(naujiena|news)']);

// Catch all

Route::get('uploads/{permalink}')->where('permalink', '.*')->middleware('main');

Route::get('{locale}/{permalink}', [UserController::class, 'getInfoPage'])->where('locale', '(lt|en)');

Route::get('/{permalink}', [UserController::class, 'getInfoPage'])->where('permalink', '.*')->middleware('main');
