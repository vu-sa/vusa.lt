<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
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
Route::feeds();

Route::get('/auth/redirect', function () {
    return Socialite::driver('microsoft')->redirect();
})->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [Admin\UserController::class, 'storeFromMicrosoft'])->name('microsoft.callback');

Route::inertia('login', 'Admin/LoginForm')->middleware('guest')->name('login');
Route::post('login', [Admin\UserController::class, 'authenticate'])->middleware('guest');

Route::group(['prefix' => '{lang?}', 'where' => ['lang' => 'lt|en'], 'middleware' => ['main']], function () {
    Route::domain('www.'.explode('.', config('app.url'), 2)[1])->group(function () {

        Route::get('nariu-registracija', [Public\PublicPageController::class, 'memberRegistration'])->name('memberRegistration');
        Route::get('kuratoriu-registracija', [Public\PublicPageController::class, 'curatorRegistration'])->name('curatorRegistration');
        Route::get('saziningai-registracija', [Public\PublicPageController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
        Route::get('saziningai-uzregistruoti-egzaminai', [Public\PublicPageController::class, 'saziningaiExams'])->name('saziningaiExams.registered');

        Route::get('pirmakursiu-stovyklos-2022', [Public\PublicPageController::class, 'summerCamps2022'])->name('pirmakursiuStovyklos2022');
        Route::get('pirmakursiu-stovyklos', [Public\PublicPageController::class, 'summerCamps2023'])->name('pirmakursiuStovyklos');
        Route::get('kalendorius/renginys/{calendar}', [Public\PublicPageController::class, 'calendarEventMain'])->name('calendar.event');

        Route::get('individualios-studijos', [Public\PublicPageController::class, 'individualStudies'])->name('individualStudies');

        Route::get('programos-klubai-projektai', [Public\PublicPageController::class, 'pkp'])->name('pkp');

        Route::post('saziningai-registracija', [Public\MainController::class, 'storeSaziningaiExamRegistration'])->name('saziningaiExamRegistration.store');
        Route::post('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'storeSaziningaiExamObserver'])->name('saziningaiExamObserver.store');
        Route::post('nariu-registracija', [Public\MainController::class, 'storeMemberRegistration'])->name('memberRegistration.store');
        Route::get('kalendorius/ics', [Public\MainController::class, 'publicAllEventCalendar'])->name('calendar.ics');
        Route::post('search', [Public\MainController::class, 'search'])->name('search');

        // Redirect reports to external subdomains
        Route::redirect('ataskaita-2022', 'https://ataskaita2022.vusa.lt', 301);
        Route::redirect('ataskaita-2023', 'https://ataskaita2023.vusa.lt', 301);
    });

    Route::domain('{subdomain}.'.explode('.', config('app.url'), 2)[1])->group(function () {
        Route::get('/', [Public\PublicPageController::class, 'home'])->name('home');
        Route::get('naujienos', [Public\NewsController::class, 'newsArchive'])->name('newsArchive');
        Route::redirect('/naujiena/archyvas', '/naujienos', 301);
        Route::redirect('/admin', '/mano', 301);

        Route::get('/apgyvendinimas', function () {
            return Redirect::to(config('app.url').'/lt/bendrabuciai', 301);
        });

        Route::get('kontaktai/id/{institution}', [Public\ContactController::class, 'institutionContacts'])->name('contacts.institution');

        Route::get('kontaktai/studentu-atstovai', [Public\ContactController::class, 'studentRepresentatives'])->name('contacts.studentRepresentatives');
        Route::get('kontaktai/{type:slug}', [Public\ContactController::class, 'institutionDutyTypeContacts'])->whereIn('type', [
            'koordinatoriai', 'kuratoriai',
        ])->name('contacts.dutyType');

        Route::get('kontaktai/{institution:alias}', [Public\ContactController::class, 'institutionContacts'])->name('contacts.alias')
            ->missing(function (Request $request) {
                return Redirect::route('contacts.institution', [
                    'institution' => $request->institution,
                    'lang' => $request->lang,
                    'subdomain' => $request->subdomain,
                ]);
            });

        Route::get('kontaktai', [Public\ContactController::class, 'contacts'])->name('contacts');
        Route::get('kontaktai/kategorija/{type:slug}', [Public\ContactController::class, 'institutionCategory'])
            ->name('contacts.category')
            ->whereIn(
                'type', ['padaliniai']
            );

        Route::get('{newsString}/{news:permalink}', [Public\NewsController::class, 'news'])
            ->whereIn('newsString', ['naujiena', 'news'])
            ->name('news');

        Route::get('mainNews', [Public\MainController::class, 'getMainNews']);
        Route::get('{permalink}', [Public\PublicPageController::class, 'page'])->where('permalink', '.*')->name('page');
    });
});

Route::get('{permalink}', [Public\PublicPageController::class, 'page'])->where('permalink', '.*');
