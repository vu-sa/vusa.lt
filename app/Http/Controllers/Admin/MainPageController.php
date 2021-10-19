<?php
namespace App\Http\Controllers\Admin;

use App\Models\Users_group;
use App\Models\MainPage;
use App\Models\News;
use App\Models\Page;
use App\Models\Padalinys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class MainPageController extends AdminBaseController {
    public function mainPage(Request $request)
    {
        $sideMenus = MainPage::where('groupID', '=', $request->User()->gid)->where('position', '=', 'sideItem')->orderBy('orderID', 'asc')->get();
        $bottomMenus = MainPage::where('groupID', '=', $request->User()->gid)->where('position', '=', 'bottomItem')->orderBy('orderID', 'asc')->get();
        $description = MainPage::where('groupID', '=', $request->User()->gid)->where('position', '=', 'additionalInfo')->get();
        $userGroupAlias = Users_group::select('alias')->where('id', '=', $request->User()->gid)->first();

        return view('pages.admin.mainPage', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'sideMenus' => $sideMenus, 'bottomMenus' => $bottomMenus,
            'userGroupAlias' => $userGroupAlias->alias, 'description' => $description, 'sideMenuSize' => sizeof($sideMenus), 'bottomMenuSize' => sizeof($bottomMenus)]);
    }

    public function getEditMainPageElement($id, Request $request)
    {
        $elementInfo = MainPage::where('id', '=', $id)->get()->first();
        return view('pages.admin.mainPageEditElement', ['currentRoute' => $this->currentRoute, 'elementInfo' => $elementInfo, 'sessionInfo' => $request->User(), 'name' => null, 'id' => $id]);
    }

    public function postEditMainPageElement($id, Request $request)
    {
        $rules = array();
        if ($request->type == 'naujiena') {
            $rules = array(
                'id' => 'required',
                'text' => 'required'
            );
        }
        if ($request->type == 'infoPage') {
            $rules = array(
                'id' => 'required',
                'link' => 'required',
                'image' => 'required'
            );
        }
        if ($request->type == 'modulis') {
            $rules = array(
                'id' => 'required',
                'moduleName' => 'required'
            );
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::to('/admin/pagrindinis/' . $id)->withInput()->withErrors(($validator));
        } else {
            $newsID = News::select('id')->where('title', '=', $request->text)->first();
            MainPage::where('id', '=', $id)->update([
                'link' => $request->link,
                'image' => $request->image,
                'newsID' => $newsID['id'],
                'text' => $request->text,
                'type' => $request->type,
                'position' => $request->type,
                'moduleName' => $request->moduleName
            ]);

            return redirect('/admin/pagrindinis')->with('message', 'Meniu punktas atnaujintas.');
        }
    }

    public function getAddPadalinysMainPageElement(Request $request, $groupAlias)
    {
        $position = $request->input('position');
        if ($position == 'side')
            $position = 'sideItem';
        elseif ($position == 'bottom')
            $position = 'bottomItem';

        $gid = Users_group::select('id')->where('alias', '=', $groupAlias)->first();
        $gid = $gid->id;
        $contents = Page::where('editorG', '=', $gid)->get();

        $pageContent = array();
        foreach ($contents as $content) {
            $pageContent[$content->id] = $content->id . '. ' . $content->title;
        }

        $news = News::where('publisher', '=', $gid)->get();
        $newsContent = array();
        foreach ($news as $new) {
            $newsContent[$new->id] = $new->id . '. ' . $new->title;
        }

        return view('pages.admin.mainPagePadalinysAdd', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'position' => $position, 'pageContent' =>
            $pageContent, 'newsContent' => $newsContent]);
    }

    public function postAddPadalinysMainPageElement(Request $request)
    {
        $rules = array(//            'text' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);
        $userGroupAlias = Users_group::select('alias')->where('id', '=', $request->User()->gid)->first();

        if ($validator->fails()) {
            if ($request->type == 'bottomItem')
                return Redirect::to('/admin/pagrindinis/' . $userGroupAlias->alias . '/prideti?position=bottom')->withInput()->withErrors(($validator));
            elseif ($request->type == 'lowbottom')
                return Redirect::to('/admin/pagrindinis/' . $userGroupAlias->alias . '/prideti?position=lowbottom')->withInput()->withErrors(($validator));
            else
                return Redirect::to('/admin/pagrindinis/' . $userGroupAlias->alias . '/prideti?position=side')->withInput()->withErrors(($validator));
        } else {
            if ($request->position == 'lowbottom') {
            } else {
                if ($request->type == 'page') {
                    if (sizeof($request->pageContent) == 1) {
                        $page = Page::where('id', '=', $request->pageContent[0])->first();
                        $link = '/lt' . $page->permalink;
                    } else {
                        # Reikia padaryt
                    }

                    $mainPage = new MainPage();
                    $mainPage->link = $link;
                    $mainPage->position = $request->position;
                    $mainPage->type = $request->type;
                    $mainPage->moduleName = 'links';
                    $mainPage->groupID = $request->User()->gid;
                    $mainPage->text = $page->title;
                    $mainPage->save();
                } else if ($request->type == 'news') {
                    $news = News::where('id', '=', $request->newsContent[0])->first();
//                    $link = '/lt/padalinys/' . $userGroupAlias->alias . '/' . $news->permalink;
                    $link = '/lt/' . $news->permalink;

                    $mainPage = new MainPage();
                    $mainPage->link = $link;
                    $mainPage->position = $request->position;
                    $mainPage->type = $request->type;
                    $mainPage->moduleName = 'links';
                    $mainPage->groupID = $request->User()->gid;
                    $mainPage->text = $news->title;
                    $mainPage->save();
                } else if ($request->type == 'link') {
                    $mainPage = new MainPage();
                    $mainPage->link = $request->link;
                    $mainPage->text = $request->text;
                    $mainPage->position = $request->position;
                    $mainPage->type = $request->type;
                    $mainPage->moduleName = 'links';
                    $mainPage->groupID = $request->User()->gid;
                    $mainPage->lang = $request->lang;
                    $mainPage->save();
                }
            }
        }

        return redirect('admin/pagrindinis/' . $request->User()->$userGroupAlias)->with('message', 'Meniu punktas sukurtas.');
    }

    public function getEditPadalinysMainPageElement($groupAlias, $id, Request $request)
    {
        $position = $request->input('position');

        $gid = $request->User()->gid;
        if ($position == 'lowbottom')
            $mainPageItem = MainPage::where('groupID', '=', $gid)->where('position', '=', 'additionalInfo')->first();
        else
            $mainPageItem = MainPage::where('groupID', '=', $gid)->where('id', '=', $id)->first();

        if ($mainPageItem == null) {
            $mainPageItem = new MainPage();
            $mainPageItem->type = 'additionalInfo';
        }

        $contents = Page::where('editorG', '=', $gid)->get();

        $pageContent = array();
        foreach ($contents as $content) {
            $pageContent[$content->id] = $content->id . '. ' . $content->title;
        }

        $news = News::where('publisher', '=', $gid)->get();
        $newsContent = array();
        foreach ($news as $new) {
            $newsContent[$new->id] = $new->id . '. ' . $new->title;
        }

        return view('pages.admin.mainPagePadalinysEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'mainPageItem' => $mainPageItem,
            'pageContent' => $pageContent, 'newsContent' => $newsContent, 'position' => $position]);
    }

    public function postEditPadalinysMainPageElement($groupAlias, $id, Request $request)
    {
        $rules = array(
            'text' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);
        $userGroupAlias = Users_group::select('alias')->where('id', '=', $request->User()->gid)->first();

        if ($validator->fails()) {
            if ($request->type == 'bottomItem')
                return Redirect::to('/admin/pagrindinis/' . $userGroupAlias->alias . '/prideti?position=bottom')->withInput()->withErrors(($validator));
            elseif ($request->type == 'lowbottom')
                return Redirect::to('/admin/pagrindinis/' . $userGroupAlias->alias . '/prideti?position=lowbottom')->withInput()->withErrors(($validator));
            else
                return Redirect::to('/admin/pagrindinis/' . $userGroupAlias->alias . '/prideti?position=side')->withInput()->withErrors(($validator));
        } else {
            if ($request->position == 'lowbottom') {
                MainPage::where('position', '=', 'additionalInfo')->where('groupID', '=', $request->User()->gid)->update([
                    'text' => $request->text,
                    'type' => $request->type
                ]);
                $msg = 'Aprašymas atnaujintas';
            } else {
                if ($request->position == 'side')
                    $request->position = 'sideItem';
                elseif ($request->position == 'bottom')
                    $request->position = 'bottomItem';

//                $request->text = str_replace(array('ą', 'č', 'ę', 'ė', 'į', 'š', 'ų', 'ū', 'ž', '"', '-', ' ', ','), array('a', 'c', 'e', 'e', 'i', 's', 'u', 'u', 'z', '', '', '-', ''),$request->text);

                if ($request->type == 'page') {
                    if (sizeof($request->pageContent) == 1) {
                        $page = Page::where('id', '=', $request->pageContent[0])->first();
                        $link = '/lt/' . $page->permalink;
                    } else {
                        # Reikia padaryt
                    }

                    MainPage::where('id', '=', $id)->update([
                        'link' => $link,
                        'type' => $request->type,
                        'moduleName' => 'links',
                        'groupID' => $request->User()->gid,
                        'text' => $page->title,
                        'position' => $request->position
                    ]);
                } else if ($request->type == 'news') {
                    $news = News::where('id', '=', $request->newsContent[0])->first();
//                    $link = '/lt/padalinys/' . $userGroupAlias->alias . '/' . $news->permalink;
                    $link = '/lt/' . $news->permalink;

                    MainPage::where('id', '=', $id)->update([
                        'link' => $link,
                        'position' => $request->position,
                        'type' => $request->type,
                        'moduleName' => 'links',
                        'groupID' => $request->User()->gid,
                        'text' => $news->title,
                    ]);

                } else if ($request->type == 'link') {
                    MainPage::where('id', '=', $id)->update([
                        'link' => $request->link,
                        'text' => $request->text,
                        'position' => $request->position,
                        'type' => $request->type,
                        'moduleName' => 'links',
                        'groupID' => $request->User()->gid,
                        'lang' => $request->lang
                    ]);
                }
                $msg = 'Meniu punktas atnaujintas';
            }
        }

        return redirect('admin/pagrindinis/')->with('message', $msg);
    }

    public function getDeletePadalinysMainPageElement(Request $request)
    {
        $itemId = $request->input('itemId');

        if (MainPage::where('id', '=', $itemId)->delete() == 1) {
            return response()->json('DELETED', 200);
        } else {
            return response()->json('NOT DELETED', 200);
        }
    }

    public function getSwapUpPadalinysMainPageElement($id, Request $request)
    {
        $position = $request->input('position');
        $selectedElement = MainPage::where('id', '=', $id)->where('position', '=', $position)->first();
        $upperElement = MainPage::where('groupID', '=', $selectedElement['groupID'])->where('orderID', '=', $selectedElement['orderID'] - 1)->where('position', '=', $position)->first();

        MainPage::where('id', '=', $selectedElement['id'])->update(['orderID' => $upperElement['orderID']]);
        MainPage::where('id', '=', $upperElement['id'])->update(['orderID' => $selectedElement['orderID']]);

        return back();
    }

    public function getSwapDownPadalinysMainPageElement($id, Request $request)
    {
        $position = $request->input('position');
        $selectedElement = MainPage::where('id', '=', $id)->where('position', '=', $position)->first();
        $lowerElement = MainPage::where('groupID', '=', $selectedElement['groupID'])->where('orderID', '=', $selectedElement['orderID'] + 1)->where('position', '=', $position)->first();

        MainPage::where('id', '=', $selectedElement['id'])->update(['orderID' => $lowerElement['orderID']]);
        MainPage::where('id', '=', $lowerElement['id'])->update(['orderID' => $selectedElement['orderID']]);

        return back();
    }

    /**
     * Padaliniai
     */

    public function padaliniai(Request $request)
    {
        $padaliniai = Padalinys::get();

        return view('pages.admin.padaliniai', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'padaliniai' => $padaliniai]);
    }

    public function getAddPadaliniai(Request $request)
    {
        return view('pages.admin.padaliniaiAdd', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null]);
    }
}