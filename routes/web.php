<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;


/**
 * |--------------------------------------------------------------------------
 * | Routes File
 * |--------------------------------------------------------------------------
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you will register all of the routes in an application.
 * | It's a breeze. Simply tell Laravel the URIs it should respond to
 * | and give it the controller to call when that URI is requested.
 * |
 */

/**
 * Statiniai tinklapio routai
 */

Log::info('Start of routes\web with ' . request()->path());

    $http_host = request()->getHttpHost();

    $padaliniaiDomains = array('www.chgf.vusa.lt', 'chgf.vusa.lt', 'evaf.vusa.lt', 'www.evaf.vusa.lt', 'ff.vusa.lt', 'www.ff.vusa.lt', 'filf.vusa.lt', 'www.filf.vusa.lt', 'fsf.vusa.lt',
            'www.fsf.vusa.lt', 'gmc.vusa.lt', 'www.gmc.vusa.lt', 'naujas.if.vusa.lt', 'if.vusa.lt', 'www.if.vusa.lt', 'kf.vusa.lt', 'www.kf.vusa.lt','knf.vusa.lt', 'www.knf.vusa.lt', 'knfsa.lt', 'www.knfsa.lt', 'mf.vusa.lt', 'www.mf.vusa.lt',
            'mif.vusa.lt', 'www.sa.vusa.lt', 'sa.vusa.lt', 'www.mif.vusa.lt', 'tf.vusa.lt', 'www.tf.vusa.lt', 'tspmi.vusa.lt', 'www.tspmi.vusa.lt', 'vm.vusa.lt', 'www.vm.vusa.lt', 'if.vusa.testas:8000');

    $vusaDomains = array('vusa.lt', 'www.vusa.lt', 'naujas.vusa.lt', 'vusa.testas:8000');

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
        
        // Grąžina JSON pavidalu 4 svarbiausias naujienas. Turbūt naudojamas elranai.vusa.lt puslapiui
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
        /**
         * |--------------------------------------------------------------------------
         * | Application Routes
         * |--------------------------------------------------------------------------
         * |
         * | This route group applies the "web" middleware group to every route
         * | it contains. The "web" middleware group is defined in your HTTP
         * | kernel and includes session state, CSRF protection, and more.
         * |
         */
        
         Route::group(['middleware'], function () {
            Auth::routes();
            
            Route::get('admin/atnaujinimai', [Admin\OtherController::class, 'getChangelog']);

            Route::get('admin', [Admin\OtherController::class, 'index']);
            Route::patch('admin', [Admin\OtherController::class, 'updateEN'])->middleware('can:handleEnConfig,App\Models\Padaliniai');
            // Route::get('admin/profilis/{username}', [Admin\OtherController::class, 'profile']);
            Route::get('admin/failai', [Admin\OtherController::class, 'getFileManager'])->middleware('can:handleFiles,App\Models\User');

            /**
             * Pagrindinis puslapis
             */
            Route::get('admin/pagrindinis', [Admin\MainPageController::class, 'mainPage'])->middleware('can:handle,App\Models\MainPage');
            Route::get('admin/pagrindinis/delete', [Admin\MainPageController::class, 'getDeletePadalinysMainPageElement'])->middleware('can:handle,App\Models\MainPage');
            Route::get('admin/pagrindinis/{id}', [Admin\MainPageController::class, 'getEditMainPageElement'])->middleware('can:handleMain,App\Models\MainPage');
            Route::post('admin/pagrindinis/{id}', [Admin\MainPageController::class, 'postEditMainPageElement'])->middleware('can:handleMain,App\Models\MainPage');
            Route::patch('admin/pagrindinis/{id}', [Admin\MainPageController::class, 'postEditMainPageElement'])->middleware('can:handleMain,App\Models\MainPage');

            /**
             * Padaliniai
             */
            Route::get('admin/padaliniai', [Admin\MainPageController::class, 'padaliniai'])->middleware('can:handle,App\Models\Padalinys');
            Route::get('admin/padaliniai/prideti', [Admin\MainPageController::class, 'getAddPadaliniai'])->middleware('can:handle,App\Models\Padalinys');
            // Route::get('admin/padaliniai/{groupAlias}/redaguoti', [Admin\MainPageController::class, '');
            // Route::get('admin/puslapiai/padalinys', [Admin\PagesController::class, 'getPadalinysPages']);

            Route::get('admin/pagrindinis/{groupAlias}/prideti', [Admin\MainPageController::class, 'getAddPadalinysMainPageElement'])->middleware('can:handle,App\Models\MainPage');
            Route::post('admin/pagrindinis/{groupAlias}/prideti', [Admin\MainPageController::class, 'postAddPadalinysMainPageElement'])->middleware('can:handle,App\Models\MainPage');
            Route::patch('admin/pagrindinis/{groupAlias}/prideti', [Admin\MainPageController::class, 'postAddPadalinysMainPageElement'])->middleware('can:handle,App\Models\MainPage');

            Route::get('admin/pagrindinis/{groupAlias}/{id}/redaguoti', [Admin\MainPageController::class, 'getEditPadalinysMainPageElement'])->middleware('can:handle,App\Models\MainPage');
            Route::post('admin/pagrindinis/{groupAlias}/{id}/redaguoti', [Admin\MainPageController::class, 'postEditPadalinysMainPageElement'])->middleware('can:handle,App\Models\MainPage');
            Route::patch('admin/pagrindinis/{groupAlias}/{id}/redaguoti', [Admin\MainPageController::class, 'postEditPadalinysMainPageElement'])->middleware('can:handle,App\Models\MainPage');

            Route::get('admin/pagrindinis/swap/{id}/up', [Admin\MainPageController::class, 'getSwapUpPadalinysMainPageElement'])->middleware('can:handle,App\Models\MainPage');
            Route::get('admin/pagrindinis/swap/{id}/down', [Admin\MainPageController::class, 'getSwapDownPadalinysMainPageElement'])->middleware('can:handle,App\Models\MainPage');

            /**
             * Navigacija
             */
            Route::get('admin/navigacijaLT', [Admin\NavigationController::class, 'navigation'])->middleware('can:handle,App\Models\Navigation');
            Route::get('admin/navigacijaEN', [Admin\NavigationController::class, 'navigation'])->middleware('can:handle,App\Models\Navigation');
            Route::get('admin/navigacija/prideti', [Admin\NavigationController::class, 'getAddNavigation'])->middleware('can:handle,App\Models\Navigation');
            Route::post('admin/navigacija/prideti', [Admin\NavigationController::class, 'postAddNavigation'])->middleware('can:handle,App\Models\Navigation');
            Route::get('admin/navigacija/subcats', [Admin\NavigationController::class, 'getNavigationParent'])->middleware('can:handle,App\Models\Navigation');
            Route::get('admin/navigacija/changeView', [Admin\NavigationController::class, 'getChangeViewNavigation'])->middleware('can:handle,App\Models\Navigation');
            Route::get('admin/navigacija/deleteRow', [Admin\NavigationController::class, 'getDeleteRowNavigation'])->middleware('can:handle,App\Models\Navigation');
            Route::get('admin/navigacija/{id}/redaguoti', [Admin\NavigationController::class, 'getUpdateNavigation'])->middleware('can:handle,App\Models\Navigation');
            Route::patch('admin/navigacija/{id}/redaguoti', [Admin\NavigationController::class, 'postUpdateNavigation'])->middleware('can:handle,App\Models\Navigation');

            Route::get('admin/navigacija/swap/{id}/up', [Admin\NavigationController::class, 'getSwapUpNavigation'])->middleware('can:handle,App\Models\Navigation');
            Route::get('admin/navigacija/swap/{id}/down', [Admin\NavigationController::class, 'getSwapDownNavigation'])->middleware('can:handle,App\Models\Navigation');

            /**
             * Puslapiai
             */
            Route::get('admin/puslapiaiLT', [Admin\PagesController::class, 'pages'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/puslapiaiEN', [Admin\PagesController::class, 'pages'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/puslapiai/prideti', [Admin\PagesController::class, 'getAddPage'])->middleware('can:handle,App\Models\Page');
            Route::post('admin/puslapiai/prideti', [Admin\PagesController::class, 'postAddPage'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/puslapiai/{permalink}/redaguoti', [Admin\PagesController::class, 'getUpdatePage'])->middleware('can:handle,App\Models\Page');
            Route::patch('admin/puslapiai/{permalink}/redaguoti', [Admin\PagesController::class, 'postUpdatePage'])->middleware('can:handle,App\Models\Page');
            Route::post('admin/puslapiai/{permalink}/redaguoti', [Admin\PagesController::class, 'postUpdatePage'])->middleware('can:handle,App\Models\Page');
            Route::post('admin/puslapiai/{permalink}/prideti', [Admin\PagesController::class, 'postAddPage'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/puslapiai/pageName', [Admin\PagesController::class, 'getInfoPagesName'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/puslapiai/deleteRow', [Admin\PagesController::class, 'deletePage'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/puslapiai/changeView', [Admin\PagesController::class, 'getChangeViewPage'])->middleware('can:handle,App\Models\Page');

            /**
             * Naujienos
             */
            Route::get('admin/naujienosLT', [Admin\PagesController::class, 'news'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/naujienosEN', [Admin\PagesController::class, 'news'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/naujienos/prideti', [Admin\PagesController::class, 'getAddNew'])->middleware('can:handle,App\Models\Page');
            Route::post('admin/naujienos/prideti', [Admin\PagesController::class, 'postAddNew'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/naujienos/{permalink}/redaguoti', [Admin\PagesController::class, 'getUpdateNew'])->middleware('can:handle,App\Models\Page');
            Route::patch('admin/naujienos/{permalink}/redaguoti', [Admin\PagesController::class, 'postUpdateNew'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/naujienos/newsName', [Admin\PagesController::class, 'getNewsName'])->middleware('can:handle,App\Models\Page');
            Route::get('admin/naujienos/deleteRow', [Admin\PagesController::class, 'deleteNew'])->middleware('can:handle,App\Models\Page');

            /**
             * Vartotojai
             */
            Route::get('admin/vartotojai', [Admin\UserController::class, 'users'])->middleware('can:handleUsers,App\Models\User');
            Route::get('admin/vartotojai/prideti', [Admin\UserController::class, 'getCreateUser'])->middleware('can:handleUsers,App\Models\User');;
            Route::post('admin/vartotojai/prideti', [Admin\UserController::class, 'postCreateUser'])->middleware('can:handleUsers,App\Models\User');;
            Route::get('admin/vartotojai/{username}/redaguoti', [Admin\UserController::class, 'getUpdateUser'])->middleware('can:handleUsers,App\Models\User');;
            Route::post('admin/vartotojai/{username}/redaguoti', [Admin\UserController::class, 'postUpdateUser'])->middleware('can:handleUsers,App\Models\User');;
            Route::patch('admin/vartotojai/{username}/redaguoti', [Admin\UserController::class, 'postUpdateUser'])->middleware('can:handleUsers,App\Models\User');;
            Route::get('admin/vartotojai/{username}/keistislaptazodi', [Admin\UserController::class, 'getChangeUserPassword'])->middleware('can:handleUsers,App\Models\User');;
            Route::post('admin/vartotojai/{username}/keistislaptazodi', [Admin\UserController::class, 'postChangeUserPassword'])->middleware('can:handleUsers,App\Models\User');;
            Route::patch('admin/vartotojai/{username}/keistislaptazodi', [Admin\UserController::class, 'postChangeUserPassword'])->middleware('can:handleUsers,App\Models\User');;
            Route::get('admin/vartotojai/deleteRow', [Admin\UserController::class, 'deleteUser'])->middleware('can:handleUsers,App\Models\User');;

            /**
             * Vartotojų grupės
             */
            Route::get('admin/grupes', [Admin\UserController::class, 'groups'])->middleware('can:handleUsers,App\Models\User');

            /**
             * Reklaminiai baneriai
             */
            Route::get('admin/reklama', [Admin\AdvertController::class, 'advert'])->middleware('can:handle,App\Models\Banner');
            Route::get('admin/reklama/prideti', [Admin\AdvertController::class, 'getAddAdvert'])->middleware('can:handle,App\Models\Banner');
            Route::post('admin/reklama/prideti', [Admin\AdvertController::class, 'postAddAdvert'])->middleware('can:handle,App\Models\Banner');
            Route::get('admin/reklama/{id}/redaguoti', [Admin\AdvertController::class, 'getEditAdvert'])->middleware('can:handle,App\Models\Banner');
            Route::patch('admin/reklama/{id}/redaguoti', [Admin\AdvertController::class, 'postEditAdvert'])->middleware('can:handle,App\Models\Banner');
            Route::get('admin/reklama/deleteRow', [Admin\AdvertController::class, 'deleteAdvert'])->middleware('can:handle,App\Models\Banner');
            Route::get('admin/reklama/changeView', [Admin\AdvertController::class, 'getChangeViewAdvert'])->middleware('can:handle,App\Models\Banner');

            /**
             * Kontaktai
             */
            Route::get('admin/kontaktai/prideti', [Admin\ContactController::class, 'getAddContact'])->middleware('can:handle,App\Models\Contact');
            Route::post('admin/kontaktai/prideti', [Admin\ContactController::class, 'postAddContact'])->middleware('can:handle,App\Models\Contact');
            Route::get('admin/kontaktai/deleteRow', [Admin\ContactController::class, 'deleteContact'])->middleware('can:handle,App\Models\Contact');
            Route::get('admin/kontaktai/{id}/redaguoti', [Admin\ContactController::class, 'getEditContact'])->middleware('can:handle,App\Models\Contact');
            Route::post('admin/kontaktai/{id}/redaguoti', [Admin\ContactController::class, 'postEditContact'])->middleware('can:handle,App\Models\Contact');
            Route::patch('admin/kontaktai/{id}/redaguoti', [Admin\ContactController::class, 'postEditContact'])->middleware('can:handle,App\Models\Contact');
            Route::get('admin/kontaktai/{name}', [Admin\ContactController::class, 'contactList'])->middleware('can:handle,App\Models\Contact');

            Route::get('admin/kontaktai/swap/{id}/up', [Admin\ContactController::class, 'getSwapContactUp'])->middleware('can:handle,App\Models\Contact');
            Route::get('admin/kontaktai/swap/{id}/down', [Admin\ContactController::class, 'getSwapContactDown'])->middleware('can:handle,App\Models\Contact');

            /**
             * Kalendorius
             */
            Route::get('admin/kalendorius', [Admin\AgendaController::class, 'calendar'])->middleware('can:handle,App\Models\Agenda');
            Route::get('admin/kalendorius/prideti', [Admin\AgendaController::class, 'getAddCalendar'])->middleware('can:handle,App\Models\Agenda');
            Route::post('admin/kalendorius/prideti', [Admin\AgendaController::class, 'postAddCalendar'])->middleware('can:handle,App\Models\Agenda');
            Route::get('admin/kalendorius/{id}/redaguoti', [Admin\AgendaController::class, 'getEditCalendar'])->middleware('can:handle,App\Models\Agenda');
            Route::patch('admin/kalendorius/{id}/redaguoti', [Admin\AgendaController::class, 'postEditCalendar'])->middleware('can:handle,App\Models\Agenda');
            Route::post('admin/kalendorius/{id}/redaguoti', [Admin\AgendaController::class, 'postEditCalendar'])->middleware('can:handle,App\Models\Agenda');
            Route::get('admin/kalendorius/deleteRowCalendar', [Admin\AgendaController::class, 'deleteCalendar'])->middleware('can:handle,App\Models\Agenda');

            /**
             * Darbotvarkė
             */
            Route::get('admin/darbotvarke', [Admin\AgendaController::class, 'agenda'])->middleware('can:handle,App\Models\Agenda');
            Route::get('admin/darbotvarke/prideti', [Admin\AgendaController::class, 'getAddAgenda'])->middleware('can:handle,App\Models\Agenda');
            Route::post('admin/darbotvarke/prideti', [Admin\AgendaController::class, 'postAddAgenda'])->middleware('can:handle,App\Models\Agenda');
            Route::get('admin/darbotvarke/{id}/redaguoti', [Admin\AgendaController::class, 'getEditAgenda'])->middleware('can:handle,App\Models\Agenda');
            Route::patch('admin/darbotvarke/{id}/redaguoti', [Admin\AgendaController::class, 'postEditAgenda'])->middleware('can:handle,App\Models\Agenda');
            Route::post('admin/darbotvarke/{id}/redaguoti', [Admin\AgendaController::class, 'postEditAgenda'])->middleware('can:handle,App\Models\Agenda');
            Route::get('admin/darbotvarke/deleteRowAgenda', [Admin\AgendaController::class, 'deleteAgenda'])->middleware('can:handle,App\Models\Agenda');

            /**
             * Sąžiningai
             */
            Route::get('admin/saziningai', [Admin\ExamController::class, 'exams'])->middleware('can:handle,App\Models\Saziningai');
            Route::get('admin/saziningai/{uuid}/redaguoti', [Admin\ExamController::class, 'getEditExam'])->middleware('can:handle,App\Models\Saziningai');
            Route::patch('admin/saziningai/{uuid}/redaguoti', [Admin\ExamController::class, 'postEditExam'])->middleware('can:handle,App\Models\Saziningai');
            Route::post('admin/saziningai/{uuid}/redaguoti', [Admin\ExamController::class, 'postEditExam'])->middleware('can:handle,App\Models\Saziningai');
            Route::get('admin/saziningai/delete', [Admin\ExamController::class, 'deleteExam'])->middleware('can:handle,App\Models\Saziningai');

            Route::get('admin/saziningai-uzsiregistrave', [Admin\ExamController::class, 'getRegisteredExamPeople'])->middleware('can:handle,App\Models\Saziningai');
            Route::get('admin/saziningai-uzsiregistrave/{id}/redaguoti', [Admin\ExamController::class, 'getEditRegisteredExamPeople'])->middleware('can:handle,App\Models\Saziningai');
            Route::post('admin/saziningai-uzsiregistrave/{id}/redaguoti', [Admin\ExamController::class, 'postEditRegisteredExamPeople'])->middleware('can:handle,App\Models\Saziningai');
            Route::patch('admin/saziningai-uzsiregistrave/{id}/redaguoti', [Admin\ExamController::class, 'postEditRegisteredExamPeople'])->middleware('can:handle,App\Models\Saziningai');
            Route::get('admin/saziningai-uzsiregistrave/delete', [Admin\ExamController::class, 'deleteRegisteredExamPeople'])->middleware('can:handle,App\Models\Saziningai');

        });

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

        Log::info('End of routes\web with '. request()->path());
