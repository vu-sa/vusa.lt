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
Route::feeds();

Route::get('/auth/redirect', function () {
    return Socialite::driver('microsoft')->redirect();
})->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [Admin\UserController::class, 'storeFromMicrosoft'])->name('microsoft.callback');

Route::inertia('login', 'LoginForm')->middleware('guest')->name('login');
Route::post('login', [Admin\UserController::class, 'authenticate'])->middleware('guest');

Route::group(['prefix' => '{lang?}', 'where' => ['lang' => 'lt|en'], 'middleware' => ['main']], function () {
    Route::domain('www.'.explode('.', config('app.url'), 2)[1])->group(function () {
        Route::get('ataskaita-2022', [Public\MainController::class, 'ataskaita2022']);
        Route::get('ataskaita-2022/{permalink}', [Public\MainController::class, 'ataskaita2022'])->where('permalink', '.*')->name('ataskaita2022');

        Route::get('saziningai-registracija', [Public\MainController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
        Route::post('saziningai-registracija', [Public\MainController::class, 'storeSaziningaiExamRegistration'])->name('saziningaiExamRegistration.store');
        Route::get('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'saziningaiExams'])->name('saziningaiExams.registered');
        Route::post('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'storeSaziningaiExamObserver'])->name('saziningaiExamObserver.store');

        Route::get('nariu-registracija', [Public\MainController::class, 'memberRegistration'])->name('memberRegistration');
        Route::post('nariu-registracija', [Public\MainController::class, 'storeMemberRegistration'])->name('memberRegistration.store');

        Route::get('kuratoriu-registracija', [Public\MainController::class, 'curatorRegistration'])->name('curatorRegistration');

        Route::get('pirmakursiu-stovyklos', [Public\MainController::class, 'summerCamps'])->name('pirmakursiuStovyklos');
        Route::get('kalendorius/renginys/{calendar}', [Public\MainController::class, 'calendarEventMain'])->name('calendar.event');
        Route::get('kalendorius/ics', [Public\MainController::class, 'publicAllEventCalendar'])->name('calendar.ics');

        Route::post('search', [Public\MainController::class, 'search'])->name('search');
    });

    Route::domain('{padalinys?}.'.explode('.', config('app.url'), 2)[1])->group(function () {
        Route::get('/', [Public\MainController::class, 'home'])->name('home');
        Route::get('naujienos', [Public\NewsController::class, 'newsArchive'])->name('newsArchive');
        Route::redirect('/naujiena/archyvas', '/naujienos', 301);
        Route::redirect('/admin', '/mano', 301);

        Route::get('kontaktai', [Public\ContactController::class, 'contacts'])->name('contacts');
        Route::get('kontaktai/kategorija/{alias}', [Public\ContactController::class, 'contactsCategory'])->name('contacts.category');
        Route::get('kontaktai/{alias}', [Public\ContactController::class, 'contactsPage'])->name('contacts.alias');

        Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('news_string', '(naujiena|news)')->name('news');

        Route::get('mainNews', [Public\MainController::class, 'getMainNews']);
        Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');
    });
});

Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*');
