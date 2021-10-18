<?php
namespace App\Http\Controllers\Admin;

use App\Models\Navigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class NavigationController extends AdminBaseController {
    /**
     * Tinklapio navigacijos valdymas
     */
    public function navigation(Request $request)
    {
        Log::info('Start of ' . __METHOD__);
        
        if (strpos($request->path(), 'navigacijaEN') !== false) {
            $navLevel1 = Navigation::where('pid', '=', 0)->where('lang', '=', 'en')->orderBy('order')->get();
            $navLevel2 = Navigation::where('pid', '!=', 0)->where('lang', '=', 'en')->orderBy('order')->get();
        } elseif (strpos($request->path(), 'navigacijaLT') !== false) {
            $navLevel1 = Navigation::where('pid', '=', 0)->where('lang', '=', 'lt')->orderBy('order')->get();
            $navLevel2 = Navigation::where('pid', '!=', 0)->where('lang', '=', 'lt')->orderBy('order')->get();
        } else {
            $navLevel1 = Navigation::where('pid', '=', 0)->orderBy('order')->get();
            $navLevel2 = Navigation::where('pid', '!=', 0)->orderBy('order')->get();
        }

        return view('pages.admin.navigation', ['navLevel1' => $navLevel1, 'navLevel2' => $navLevel2, 'navLevel3' => $navLevel2, 'navLevel4' => $navLevel2, 'currentRoute' => request()->path(), 'sessionInfo' => $request->User(), 'name' => null]);
    }

    public function getAddNavigation(Request $request)
    {
        $navLevel1 = Navigation::where('pid', '=', 0)->get();

        $navLevel1_a[0] = "Pirmo lygio punktas";
        foreach ($navLevel1 as $navLevel1a) {
            $navLevel1_a[$navLevel1a->id] = $navLevel1a->text;
        }

        return view('pages.admin.navigationAdd', ['currentRoute' => request()->path(), 'sessionInfo' => $request->User(), 'name' => null, 'parrentCats' => $navLevel1_a]);
    }

    public function postAddNavigation(Request $request)
    {
        $rules = array(
            'text' => 'required|unique:menu_new',
            'pid' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
            return Redirect::to('/admin/navigacija/prideti')->withInput()->withErrors(($validator));
        else {
            $navItem = new Navigation();
            if ($request->pid3 == 'undefined' || "") {
                if ($request->pid2 == 'undefined' || "") {
                    $navItem->pid = $request->pid;
                } else {
                    $navItem->pid = $request->pid2;
                }
            } else {
                $navItem->pid = $request->pid3;
            }

            $navItem->text = $request->text;
            $navItem->lang = $request->lang;
            $navItem->url = $request->url;
            $navItem->order = Navigation::where('pid', '=', $navItem->pid)->count();
            if ($request->show == null) {
                $navItem->show = 0;
            } else {
                $navItem->show = $request->show;
            }
            $navItem->readonly = 0;
            $navItem->creator = $request->creator;
            $navItem->save();
        }
        if ($request->lang == 'lt')
            return redirect('/admin/navigacijaLT')->with('message', 'Navigacijos elementas sukurtas.');
        else
            return redirect('/admin/navigacijaEN')->with('message', 'Navigacijos elementas sukurtas.');
    }

    public function getNavigationParent(Request $request)
    {
        $cat_id = $request->input('cat_id');
        $navLevel2 = Navigation::select('id', 'text')->where('pid', '=', $cat_id)->get();

        return Response::json($navLevel2);
    }

    public function getChangeViewNavigation(Request $request)
    {
        $itemId = $request->input('itemId');
        $showArr = Navigation::select('show')->where('id', '=', $itemId)->first();
        if ($showArr->show == '1') {
            Navigation::where('id', '=', $itemId)->update(['show' => '0']);
        } else {
            Navigation::where('id', '=', $itemId)->update(['show' => '1']);
        }

        return "updated";
    }

    public function getDeleteRowNavigation(Request $request)
    {
        $itemId = $request->input('itemId');
        $navItem = Navigation::where('id', '=', $itemId)->first();

        if (Navigation::where('id', '=', $itemId)->delete() == 1) {
            $navigations = Navigation::where('order', '>', $navItem->order)->where('pid', '=', $navItem->pid)->get();
            foreach ($navigations as $navigation) {
                Navigation::where('id', '=', $navigation->id)->update(['order' => $navigation->order - 1]);
            }

            return response()->json('DELETED', 200);
        }

        return response()->json('NOT DELETED', 200);
    }

    public function getUpdateNavigation($id, Request $request)
    {
        $IDlist = array();
        $navInfo1 = Navigation::where('id', '=', $id)->first();

        if ($navInfo1['pid'] == 0) {
            array_push($IDlist, $navInfo1['pid']);
            $navLevel1 = Navigation::where('pid', '=', 0)->get();
            $parentCat1[0] = "Pirmo lygio punktas";
            foreach ($navLevel1 as $navLevel1a) {
                $parentCat1[$navLevel1a->id] = $navLevel1a->text;
            }
        } else {
            array_push($IDlist, $navInfo1['pid']);
            $navLevel1 = Navigation::where('pid', '=', 0)->get();
            $parentCat1[0] = "Pirmo lygio punktas";
            foreach ($navLevel1 as $navLevel1a) {
                $parentCat1[$navLevel1a->id] = $navLevel1a->text;
            }

            $navInfo2 = Navigation::where('id', '=', $navInfo1['pid'])->first();
            array_push($IDlist, $navInfo2['pid']);
            $navLevel2 = Navigation::where('pid', '=', $navInfo2['pid'])->get();

            if ($navInfo2['pid'] != 0) {
                $navInfo3 = Navigation::where('id', '=', $navInfo2['pid'])->first();
                array_push($IDlist, $navInfo3['pid']);
                $navLevel3 = Navigation::where('pid', '=', $navInfo3['pid'])->get();

                if ($navInfo3['pid'] != 0) {
                    $navInfo4 = Navigation::where('id', '=', $navInfo3['pid'])->first();
                    array_push($IDlist, $navInfo4['pid']);
                }
            }
        }

        if (sizeof($IDlist) == 1) {
            return view('pages.admin.navigationEdit', ['currentRoute' => request()->path(), 'sessionInfo' => $request->User(), 'navInfo' => $navInfo1, 'name' => null, 'arraySize' => sizeof($IDlist), 'parentId1' => $IDlist[0], 'parentCat1' => $parentCat1]);
        }

        if (sizeof($IDlist) == 2) {
            $navInfo1->pid = $IDlist[0];

            return view('pages.admin.navigationEdit', ['currentRoute' => request()->path(), 'sessionInfo' => $request->User(), 'navInfo' => $navInfo1, 'name' => null, 'arraySize' => sizeof($IDlist), 'parentCat1' => $parentCat1, 'parentId2' => $IDlist[0], 'parentCat2' => $navLevel2]);
        }

        if (sizeof($IDlist) == 3) {
            $navInfo1->pid = $IDlist[1];

            return view('pages.admin.navigationEdit', ['currentRoute' => request()->path(), 'sessionInfo' => $request->User(), 'navInfo' => $navInfo1, 'name' => null, 'arraySize' => sizeof($IDlist), 'parentId1' => $IDlist[1], 'parentCat1' => $parentCat1, 'parentId2' => $IDlist[0], 'parentCat2' => $navLevel2, 'parentId3' => $IDlist[2], 'parentCat3' => $navLevel3]);
        }

        if (sizeof($IDlist) == 4) {
            $navInfo1->pid = $IDlist[2];

            return view('pages.admin.navigationEdit', ['currentRoute' => request()->path(), 'sessionInfo' => $request->User(), 'navInfo' => $navInfo1, 'name' => null, 'arraySize' => sizeof($IDlist), 'parentId1' => $IDlist[1], 'parentCat1' => $parentCat1, 'parentId2' => $IDlist[1], 'parentCat2' => $navLevel2, 'parentId3' => $IDlist[3], 'parentCat3' => $navLevel3]);
        }
    }

    public function postUpdateNavigation($id, Request $request)
    {
        $rules = array(
            'text' => 'required',
            'url' => 'required',
            'pid' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::to('/admin/navigacija/' . $id . '/redaguoti')->withInput()->withErrors(($validator));
        } else {
            $pid = null;
            if ($request->pid3 == 'undefined' || "") {
                if ($request->pid2 == 'undefined' || "") {
                    $pid = $request->pid;
                } else {
                    $pid = $request->pid2;
                }
            } else {
                $pid = $request->pid3;
            }
            Navigation::where('id', '=', $id)->update([
                'text' => $request->text,
                'url' => $request->url,
                'pid' => $pid,
            ]);

            if ($request->lang == 'lt')
                return redirect('/admin/navigacijaLT')->with('message', 'Navigacijos punktas atnaujinta.');
            else
                return redirect('/admin/navigacijaEN')->with('message', 'Navigacijos punktas atnaujinta.');
        }
    }

    public function getSwapUpNavigation($id, Request $request)
    {
        $selectedNavItem = Navigation::where('id', '=', $id)->first();
        $upperNavItem = Navigation::where('pid', '=', $selectedNavItem['pid'])->where('order', '=', $selectedNavItem['order'] - 1)->first();

        Navigation::where('id', '=', $selectedNavItem['id'])->update(['order' => $upperNavItem['order']]);
        Navigation::where('id', '=', $upperNavItem['id'])->update(['order' => $selectedNavItem['order']]);

        return back();
    }

    public function getSwapDownNavigation($id, Request $request)
    {
        $selectedNavItem = Navigation::where('id', '=', $id)->first();
        $upperNavItem = Navigation::where('pid', '=', $selectedNavItem['pid'])->where('order', '=', $selectedNavItem['order'] + 1)->first();

        Navigation::where('id', '=', $selectedNavItem['id'])->update(['order' => $upperNavItem['order']]);
        Navigation::where('id', '=', $upperNavItem['id'])->update(['order' => $selectedNavItem['order']]);

        return back();
    }
}