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
    return Socialite::driver('microsoft')->with(['prompt' => 'select_account'])->redirect();
})->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [Admin\UserController::class, 'storeFromMicrosoft'])->name('microsoft.callback');

Route::inertia('login', 'Admin/LoginForm')->middleware('guest')->name('login');
Route::post('login', [Admin\UserController::class, 'authenticate'])->middleware('guest');

Route::post('feedback', [Public\MainController::class, 'sendFeedback'])->name('feedback.send');

Route::post('registration/{form}', [RegistrationController::class, 'store'])->name('registrations.store');

// Sitemap routes (outside language group)
Route::domain('{subdomain}.'.explode('.', config('app.url'), 2)[1])->group(function () {
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
    Route::get('/sitemap-news.xml', [SitemapController::class, 'news'])->name('sitemap.news');
    Route::get('/sitemap-news-google.xml', [SitemapController::class, 'googleNews'])->name('sitemap.news.google');
});

Route::group(['prefix' => '{lang?}', 'where' => ['lang' => 'lt|en'], 'middleware' => ['main']], function () {
    Route::domain('www.'.explode('.', config('app.url'), 2)[1])->group(function () {

        Route::get('{registrationString}/{registrationForm}', [Public\PublicPageController::class, 'registrationPage'])->name('registrationPage')->whereIn('registrationString', ['registracija', 'registration']);

        Route::get('kalendorius/renginys/{calendar}', [Public\PublicPageController::class, 'calendarEventRedirect'])->name('calendar.event');

        Route::get('tapk-vu-sa-nariu', [Public\PublicPageController::class, 'membership'])->name('joinUs');

        Route::get('kalendorius/{year}/{month}/{day}/{slug}', [Public\PublicPageController::class, 'calendarMain'])->name('calendar.event.2')->whereNumber('year')->whereNumber('month')->whereNumber('day');

        Route::get('kalendorius/renginiu-sarasas', [Public\PublicPageController::class, 'calendarEventList'])->name('calendar.list');

        Route::get('pirmakursiu-stovyklos/{year?}', [Public\PublicPageController::class, 'summerCamps'])->name('pirmakursiuStovyklos')->whereNumber('year');

        Route::get('programos-klubai-projektai', [Public\PublicPageController::class, 'pkp'])->name('pkp');

        Route::get('kategorija/{category:alias}', [Public\PublicPageController::class, 'category'])->name('category');

        Route::get('{registrationString}', [Public\PublicPageController::class, 'curatorRegistrations'])->name('curatorRegistrations')->whereIn('registrationString', ['registracija-i-kuratoriu-programa', 'registration-to-mentor-program']);

        Route::get('kalendorius/ics', [Public\MainController::class, 'publicAllEventCalendar'])->name('calendar.ics');
        Route::post('search', [Public\MainController::class, 'search'])->name('search');

        // Note: API routes should be defined in api.php, not here

        Route::get('dokumentai', [Public\PublicPageController::class, 'documents'])->name('documents');

        // Redirect reports to external subdomains
        Route::redirect('ataskaita-2022', 'https://ataskaita2022.vusa.lt', 301);
        Route::redirect('ataskaita-2023', 'https://ataskaita2023.vusa.lt', 301);
        Route::redirect('nariu-registracija', config('app.url').'/registracija/nariu-registracija', 301)->name('member-registration');
    });

    Route::domain('{subdomain}.'.explode('.', config('app.url'), 2)[1])->group(function () {
        Route::get('/', [Public\PublicPageController::class, 'home'])->name('home');
        Route::get('{newsString}', [Public\NewsController::class, 'newsArchive'])->name('newsArchive')->whereIn('newsString', ['naujienos', 'news']);
        Route::redirect('/admin', '/mano', 301);

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
