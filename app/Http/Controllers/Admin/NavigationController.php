<?php
namespace App\Http\Controllers\Admin;

use App\Models\Navigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class NavigationController extends AdminBaseController {
    
        
    /**
     * returnParentIDfromUpdate
     *
     * Returns the parent ID after POST from: 
     * Route::patch('admin/navigacija/{id}/redaguoti')
     * 
     * @param  mixed $request
     * @return void
     */

    private function returnParentIDfromUpdate($request) {
        $emptyArray = ['undefined', "", NULL];

        if (in_array($request->pid3, $emptyArray)) {
            if (in_array($request->pid2, $emptyArray)) {
                return $request->pid;
            } else {
                return $request->pid2;
            }
        } else {
            return $request->pid3;
        }
}    
    /**
     * getCorrectMenuIDOrder
     * 
     * Returns an integer of menu order after POST from:
     * Route::patch('admin/navigacija/{id}/redaguoti')
     *
     * @param  mixed $pid
     * @param  mixed $originalOrder
     * @return int
     */
    private function getCorrectMenuIDOrder($pid) {

        $menuChildren = Navigation::where('pid', '=', $pid)->select('order')->get();

        if ($menuChildren->isEmpty()) {
            return 0;
        } else { 
        return intval($menuChildren->max('order') + 1);
        }
    }

        
    /**
     * getMenuTree
     * 
     * Returns Menu Tree for Administration navigation.index route
     *
     * @param  mixed $navigacija
     * @param  mixed $pid
     * @param  mixed $depth
     * @return string
     */
    private function getMenuTree($navigacija, $pid = 0, $depth = 0) {
        
        $menu = "";
        $menuPadding = $depth + 1;
        $menuColor = "";
        $navChildren = $navigacija->where('pid', '=', $pid);

        switch ($depth) {
            case 0:
                $menuColor = 'success';
                break;
            case 1:
                $menuColor = 'warning';
                break;
            case 2:
                $menuColor = 'danger';
                break;
            case 3:
                $menuColor = 'alert';
                break;
            default:
                $menuColor = 'danger';
                break;
        }

        foreach ($navChildren as $row) {

            $menu .= '<tr class="alert alert-'. $menuColor . '">';
            $menu .= '<td style="padding-left:' . $menuPadding . 'rem" colspan="2">' . $row->text . '</td>';
            $menu .= '<td></td><td></td>';
            $menu .= '<td>' . $row->url . '</td>';
            $menu .= '<td><a class="mr-1 changeView" style="text-decoration:none" id="' . $row->id . '" aria-hidden="true"><i class="fas fa-eye';
            $menu .= $row->show == 1 ? '' : '-slash';
            $menu .= '"></i></a>';
            $menu .= '<a class="mr-1" style="text-decoration:none" href="/admin/navigacija/' . $row->id . '/redaguoti"><i class="fas fa-edit"></i></a>';
            $menu .= '<a class="mr-1 deleteRow" style="text-decoration:none" id="' . $row->id . '"aria-hidden="true"><i class="fas fa-trash"></i></a></td>';
            
            if ($navChildren->min('order') != $row->order) {
                $menu .= '<td><a style="text-decoration:none" href="/admin/navigacija/swap/'. $row->id . '/up"><i class="fas fa-chevron-up"></i></a></td>';
            } else {
                $menu .= '<td></td>'; 
            }

            if ($navChildren->max('order') != $row->order) {
                $menu .= '<td><a style="text-decoration:none" href="/admin/navigacija/swap/'. $row->id . '/down"><i class="fas fa-chevron-down"></i></a></td>';
            } else {
                $menu .= '<td></td>'; 
            }
            
            $menu .= '</tr>';

            if ($navChildren != NULL) {
                $menu .= $this->getMenuTree($navigacija, $row->id, $depth + 1);
            }
        }
        return $menu;
    }
        
    /**
     * cleanupAfterDeleting
     *
     * @param  mixed $itemId
     * @param  mixed $navItem
     * @return void
     */
    private function cleanupAfterDeleting($itemId, $navItem) {
               
        $navigationChildren = Navigation::where('pid', '=', $itemId)->get()->sortBy([
            'order', 'asc'
        ]);

        $newPidLastOrder = Navigation::where('pid', '=', $navItem->pid)->select('order')->get()->max('order');

        foreach ($navigationChildren as $navChild) {
            $newPidLastOrder++;

            Navigation::where('id', '=', $navChild->id)->update([
                'order' => $newPidLastOrder,
                'pid' => $navItem->pid,
                'show' => 0
            ]);
        }
    }

     public function navigation(Request $request)
    {  
         $navCollection = Navigation::where('lang', '=', 'lt')->get()->sortBy(
                    [
                        ['pid', 'asc'],
                        ['order', 'asc'],
                    ]
                );

        $navigacija = $this->getMenuTree($navCollection);

        return view('pages.admin.navigation', ['currentRoute' => request()->path(), 'sessionInfo' => $request->User(), 'name' => null, 'navigacija' => $navigacija]);

        
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

            if (Navigation::where('pid', '=', $itemId) != null) {
                $this->cleanupAfterDeleting($itemId, $navItem);
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
            $pid = $this->returnParentIDfromUpdate($request);

            $order = $this->getCorrectMenuIDOrder($pid);

            Navigation::where('id', '=', $id)->update([
                'text' => $request->text,
                'url' => $request->url,
                'pid' => $pid,
                'order' => $order
            ]);

            if ($request->lang == 'lt')
                return redirect('/admin/navigacijaLT')->with('message', 'Navigacijos punktas atnaujintas.');
            else
                return redirect('/admin/navigacijaEN')->with('message', 'Navigacijos punktas atnaujintas.');
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