<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetPadaliniaiForUpserts;
use App\Http\Controllers\LaravelResourceController;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\MainPage;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Page;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class MainPageController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [MainPage::class, $this->authorizer]);

        $indexer = new ModelIndexer(new MainPage(), request(), $this->authorizer);

        $mainPage = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(15);

        return Inertia::render('Admin/Content/IndexMainPage', [
            'mainPage' => $mainPage,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [MainPage::class, $this->authorizer]);

        return Inertia::render('Admin/Content/CreateMainPage', [
            'typeOptions' => Inertia::lazy(fn () => $this->getMainPageTypeOptions(request()->input('type'))),
            'padaliniaiOptions' => GetPadaliniaiForUpserts::execute('mainPages.create.all', $this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [MainPage::class, $this->authorizer]);

        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);

        if (request()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $padalinys_id = Padalinys::where('type', 'pagrindinis')->first()->id;
        } else {
            $padalinys_id = $this->authorizer->permissableDuties->first()->padaliniai->first()->id;
        }

        DB::transaction(function () use ($request, $padalinys_id) {
            $mainPage = new MainPage;

            $mainPage->text = $request->text;
            $mainPage->link = $request->link;
            $mainPage->lang = $request->lang;
            $mainPage->position = '';
            $mainPage->padalinys()->associate($padalinys_id);
            $mainPage->save();
        });

        return redirect()->route('mainPage.index')->with('success', 'Sėkmingai sukurta greitoji nuoroda!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(MainPage $mainPage)
    {
        $this->authorize('update', [MainPage::class, $mainPage, $this->authorizer]);

        // $routes = Route::getRoutes();

        // Filter the routes to include only those without parameters EXCEPT for {subdomain} and {lang}.
        // Also the routes NOT in /mano directory, debugbar, telescope, impersonate, laravel-websockets, ignition, auth, login, feed and broadcasting routes
        // $routesWithoutParams = collect($routes->getRoutesByMethod()['GET'])->filter(function ($route) {
        //     return !collect($route->parameterNames)->except(['subdomain', 'lang'])->count() &&
        //         !collect($route->getAction())->has('prefix', 'mano') &&
        //         !collect($route->getAction())->has('prefix', '_debugbar') &&
        //         !collect($route->getAction())->has('prefix', 'telescope') &&
        //         !collect($route->getAction())->has('prefix', 'impersonate') &&
        //         !collect($route->getAction())->has('prefix', 'laravel-websockets') &&
        //         !collect($route->getAction())->has('prefix', 'ignition') &&
        //         !collect($route->getAction())->has('prefix', 'auth') &&
        //         !collect($route->getAction())->has('prefix', 'login') &&
        //         !collect($route->getAction())->has('prefix', 'feed') &&
        //         !collect($route->getAction())->has('prefix', 'broadcasting');
        // });

        // dd($routesWithoutParams);

        return Inertia::render('Admin/Content/EditMainPage', [
            'mainPage' => $mainPage,
            'padaliniaiOptions' => GetPadaliniaiForUpserts::execute('mainPages.update.all', $this->authorizer),
            'typeOptions' => Inertia::lazy(fn () => $this->getMainPageTypeOptions(request()->input('type'))),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MainPage $mainPage)
    {
        $this->authorize('update', [MainPage::class, $mainPage, $this->authorizer]);

        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);

        DB::transaction(function () use ($request, $mainPage) {
            $mainPage->update($request->only('text', 'link', 'lang'));
        });

        return back()->with('success', 'Sėkmingai atnaujinta greitoji nuoroda!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(MainPage $mainPage)
    {
        $this->authorize('delete', [MainPage::class, $mainPage, $this->authorizer]);

        $mainPage->delete();

        return redirect()->route('mainPage.index')->with('info', 'Sėkmingai ištrinta greitoji nuoroda!');
    }

    public function editOrder(Padalinys $padalinys, string $lang)
    {
        $mainPages = MainPage::query()->where('padalinys_id', $padalinys->id)->where('lang', $lang)->orderBy('order')->get();

        $this->authorize('update', [MainPage::class, $mainPages->first(), $this->authorizer]);

        return Inertia::render('Admin/Content/EditMainPageOrder', [
            'mainPages' => $mainPages,
            'padalinys' => $padalinys,
        ]);
    }

    public function updateOrder(Request $request, Padalinys $padalinys)
    {
        $request->validate([
            'orderList' => 'required|array',
        ]);

        foreach ($request->orderList as $idAndOrder) {
            $this->authorize('update', [MainPage::class, MainPage::find($idAndOrder['id']), $this->authorizer]);
        }

        DB::transaction(function () use ($request) {
            foreach ($request->orderList as $idAndOrder) {
                $mainPage = MainPage::find($idAndOrder['id']);
                $mainPage->order = $idAndOrder['order'];
                $mainPage->save();
            }
        });

        return redirect()->route('mainPage.index')->with('success', 'Sėkmingai atnaujinta greitųjų nuorodų tvarka!');
    }

    public static function getMainPageTypeOptions($type)
    {
        switch ($type) {
            case 'url':
                return;

            case 'page':
                return Page::query()->with('padalinys:id,alias,shortname')->get(['id', 'lang', 'title', 'padalinys_id', 'permalink']);

            case 'news':
                return News::query()->with('padalinys:id,alias,shortname')->get(['id', 'lang', 'title', 'padalinys_id', 'permalink']);

            case 'calendarEvent':
                return Calendar::query()->with('padalinys:id,alias,shortname')->get(['id', 'title', 'padalinys_id']);

            case 'institution':
                return Institution::query()->with('padalinys:id,alias,shortname')->get(['id', 'name', 'padalinys_id']);

                // case 'special-page':
                //     return collect();

            default:
                // code...
                break;
        }
    }
}
