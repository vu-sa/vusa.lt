<?php

namespace App\Http\Controllers;

use App\Support\LocalizedRouteSlugs;
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
    // Store popup flag in session for callback handling, validating as boolean
    if (request()->has('popup') && request()->boolean('popup')) {
        session(['oauth_popup' => true]);
    }

    /** @phpstan-ignore-next-line */
    return Socialite::driver('microsoft')->stateless()->with(['prompt' => 'select_account'])->redirect();
})->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [Admin\AuthController::class, 'storeFromMicrosoft'])->name('microsoft.callback');

Route::inertia('login', 'Admin/LoginForm')->middleware('guest')->name('login');
Route::post('login', [Admin\AuthController::class, 'authenticate'])->middleware('guest');

Route::post('feedback', [Public\MainController::class, 'sendFeedback'])->name('feedback.send');

Route::post('registration/{form}', [RegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('registrations.store');

// Short URL redirects for documents
Route::get('/d/{code}', [Public\DocumentRedirectController::class, 'redirect'])
    ->where('code', '[0-9A-Za-z]+')
    ->name('document.short');

// Sitemap routes (outside language group)
Route::domain('{subdomain}.'.explode('.', config('app.url'), 2)[1])->group(function (): void {
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
    Route::get('/sitemap-news.xml', [SitemapController::class, 'news'])->name('sitemap.news');
    Route::get('/sitemap-news-google.xml', [SitemapController::class, 'googleNews'])->name('sitemap.news.google');
});

Route::group(['prefix' => '{lang?}', 'where' => ['lang' => 'lt|en'], 'middleware' => ['main']], function (): void {
    Route::domain('www.'.explode('.', config('app.url'), 2)[1])->group(function (): void {

        Route::get('{registrationString}/{registrationForm}', [Public\PublicPageController::class, 'registrationPage'])->name('registrationPage')
            ->whereIn('registrationString', LocalizedRouteSlugs::accepted('registrationString'));

        Route::get('kalendorius/renginys/{calendar}', [Public\PublicPageController::class, 'calendarEventRedirect'])->name('calendar.event');

        /* Route::get('become-a-member', [Public\PublicPageController::class, 'membership'])->name('joinUs.en'); */

        Route::get('kalendorius/{year}/{month}/{day}/{slug}', [Public\PublicPageController::class, 'calendarMain'])->name('calendar.event.2')->whereNumber('year')->whereNumber('month')->whereNumber('day');

        Route::get('kalendorius/renginiu-sarasas', [Public\PublicPageController::class, 'calendarEventList'])->name('calendar.list');

        Route::get('pirmakursiu-stovyklos/{year}', [Public\PublicPageController::class, 'summerCamps'])->name('pirmakursiuStovyklos')->whereNumber('year');

        Route::get('programos-klubai-projektai', [Public\PublicPageController::class, 'pkp'])->name('pkp');

        Route::get('kategorija/{category:alias}', [Public\PublicPageController::class, 'category'])->name('category');

        Route::get('{curatorRegistrationString}', [Public\PublicPageController::class, 'curatorRegistrations'])->name('curatorRegistrations')
            ->whereIn('curatorRegistrationString', LocalizedRouteSlugs::accepted('curatorRegistrationString'));

        Route::get('kalendorius/ics', [Public\MainController::class, 'publicAllEventCalendar'])->name('calendar.ics');

        Route::permanentRedirect('nariu-registracija', config('app.url').'/registracija/nariu-registracija')->name('member-registration');
        Route::permanentRedirect('member-registration', config('app.url').'/registration/member-registration')->name('member-registration.en');

        // Note: API routes should be defined in api.php, not here

        Route::get('{documentsString}', [Public\DocumentController::class, 'index'])->name('documents')
            ->whereIn('documentsString', LocalizedRouteSlugs::accepted('documentsString'));

        Route::get('{searchString}', [Public\SearchController::class, 'index'])->name('search')
            ->whereIn('searchString', LocalizedRouteSlugs::accepted('searchString'));

        Route::get('ind-komplektai', [Public\StudySetController::class, 'index'])->name('studySets');

        // Redirect reports to external subdomains
        Route::permanentRedirect('ataskaita-2022', 'https://ataskaita2022.vusa.lt');
        Route::permanentRedirect('ataskaita-2023', 'https://ataskaita2023.vusa.lt');
    });

    Route::domain('{subdomain}.'.explode('.', config('app.url'), 2)[1])->group(function (): void {
        Route::get('/', [Public\PublicPageController::class, 'home'])->name('home');
        Route::get('{newsArchiveString}', [Public\NewsController::class, 'newsArchive'])->name('newsArchive')
            ->whereIn('newsArchiveString', LocalizedRouteSlugs::accepted('newsArchiveString'));
        Route::permanentRedirect('/admin', '/mano');

        /* Route::get('tapk-nariu', [Public\PublicPageController::class, 'membership'])->name('joinUs'); */

        Route::get('{contactsString}/id/{institution}', [Public\ContactController::class, 'institutionContacts'])->name('contacts.institution')
            ->whereIn('contactsString', LocalizedRouteSlugs::accepted('contactsString'));

        Route::get('{meetingsString}', [Public\MeetingController::class, 'index'])->name('publicMeetings.index')
            ->whereIn('meetingsString', LocalizedRouteSlugs::accepted('meetingsString'));
        Route::get('{meetingsString}/{meeting}', [Public\ContactController::class, 'showMeeting'])->name('publicMeetings.show')
            ->whereIn('meetingsString', LocalizedRouteSlugs::accepted('meetingsString'));

        Route::get('{contactsString}/{studentRepsString}', [Public\ContactController::class, 'studentRepresentatives'])->name('contacts.studentRepresentatives')
            ->whereIn('contactsString', LocalizedRouteSlugs::accepted('contactsString'))
            ->whereIn('studentRepsString', LocalizedRouteSlugs::accepted('studentRepsString'));
        Route::get('{contactsString}/{type:slug}', [Public\ContactController::class, 'institutionDutyTypeContacts'])
            ->whereIn('contactsString', LocalizedRouteSlugs::accepted('contactsString'))
            ->whereIn('type', ['koordinatoriai', 'kuratoriai', 'mentors'])->name('contacts.dutyType');

        Route::get('{contactsString}/{institution:alias}', [Public\ContactController::class, 'institutionContacts'])->name('contacts.alias')
            ->whereIn('contactsString', LocalizedRouteSlugs::accepted('contactsString'))
            ->missing(fn (Request $request) => Redirect::route('contacts.institution', [
                'institution' => $request->institution,
                'lang' => $request->lang,
                'subdomain' => $request->subdomain,
            ]));

        Route::get('{contactsString}', [Public\ContactController::class, 'contacts'])->name('contacts')
            ->whereIn('contactsString', LocalizedRouteSlugs::accepted('contactsString'));
        Route::get('{contactsString}/{contactCategoryString}/{type:slug}', [Public\ContactController::class, 'institutionCategory'])
            ->whereIn('contactsString', LocalizedRouteSlugs::accepted('contactsString'))
            ->whereIn('contactCategoryString', LocalizedRouteSlugs::accepted('contactCategoryString'))
            ->name('contacts.category');

        Route::get('{newsString}/{news}', [Public\NewsController::class, 'news'])
            ->whereIn('newsString', ['naujiena', 'news'])
            ->name('news');

        Route::get('mainNews', [Public\MainController::class, 'getMainNews']);
        Route::get('{permalink}', [Public\PublicPageController::class, 'page'])->where('permalink', '.*')->name('page');
    });
});

Route::get('{permalink}', [Public\PublicPageController::class, 'page'])->where('permalink', '.*');
