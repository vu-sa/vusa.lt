<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use App\Models\Users_group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class AdvertController extends AdminBaseController {

    /**
     * Reklamos - banerių valdymas
     */
    public function advert(Request $request)
    {
        if ($request->User()->gid > 3) {
            $banners = Banner::where('editorG', '=', $request->User()->gid)->addSelect(['descr' => Users_group::select('descr')->whereColumn('id', 'sidebar.editorG')])->orderBy('order', 'desc')->simplePaginate(10);
        } else
            $banners = Banner::addSelect(['descr' => Users_group::select('descr')->whereColumn('id', 'sidebar.editorG')])->orderBy('order', 'desc')->simplePaginate(10);

        return view('pages.admin.advert', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'banners' => $banners, 'name' => null]);
    }

    public function getAddAdvert(Request $request)
    {
        return view('pages.admin.advertAdd', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null]);
    }

    public function postAddAdvert(Request $request)
    {
        $rules = array(
            'title' => 'required|unique:sidebar',
            'value' => 'required|unique:sidebar',
            'url' => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/reklama/prideti')->withInput()->withErrors(($validator));
        } else {
            $orderID = Banner::orderBy('order', 'desc')->first();
            $banner = new Banner();
            $banner->type = 'image';
            $banner->title = $request->title;
            isset($request->hide) ? $banner->hide = $request->hide : $banner->hide = 0;
            $banner->value = $request->value;
            $banner->url = $request->url;
            $banner->order = $orderID['order'] + 1;
            $banner->editor = $request->User()->id;
            $banner->editorG = $request->User()->gid;
            $banner->save();
        }

        return redirect('/admin/reklama')->with('message', 'Baneris pridėtas.');
    }

    public function deleteAdvert(Request $request)
    {
        $itemId = $request->input('itemId');
        $bannerInfo = Banner::where('id', '=', $itemId)->first();
        if (strpos($bannerInfo['image'], 'vusa.lt') !== false) {
            $imageLocation = $bannerInfo['image'];
        } else {
            $imageLocation = 'uploads/sidebar/' . $bannerInfo['value'];
        }
//        Storage::delete($imageLocation);
        if (Banner::where('id', '=', $itemId)->delete() == 1) {
            $banners = Banner::where('order', '>', $bannerInfo['order'])->get();
            foreach ($banners as $banner) {
                Banner::where('id', '=', $banner->id)->update(['order' => $banner->order - 1]);
            }
            return response()->json('DELETED', 200);
        } else {
            return response()->json('NOT DELETED', 200);
        }
    }

    public function getEditAdvert($id, Request $request)
    {
        $banners = Banner::where('id', '=', $id)->first();

        return view('pages.admin.advertEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'banners' => $banners, 'name' => null, 'pageInfo' => null]);
    }

    public function postEditAdvert($id, Request $request)
    {
        $rules = array(
            'title' => 'required',
            'value' => 'required',
            'url' => 'required',
            'hide' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/reklama/' . $id . '/redaguoti')->withInput()->withErrors(($validator));
        } else {
            Banner::where('id', '=', $id)->update([
                'type' => 'image',
                'title' => $request->title,
                'hide' => $request->hide == true ? 1 : 0,
                'value' => $request->value,
                'url' => $request->url,
                'editor' => $request->User()->id
            ]);
        }

        return redirect('/admin/reklama')->with('message', 'Baneris atnaujintas.');
    }

    public function getChangeViewAdvert(Request $request)
    {
        $itemId = $request->input('itemId');
        $showArr = Banner::select('hide')->where('id', '=', $itemId)->first();
        if ($showArr->hide == '1') {
            Banner::where('id', '=', $itemId)->update(['hide' => '0']);
        } else {
            Banner::where('id', '=', $itemId)->update(['hide' => '1']);
        }

        return "updated";
    }
}