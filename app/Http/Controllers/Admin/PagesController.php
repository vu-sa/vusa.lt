<?php
namespace App\Http\Controllers\Admin;

use App\Models\Users_group;
use App\Models\Page;
use App\Models\PagesCat;
use App\Models\News;
use App\Models\NewsCat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Intervention\Image\ImageManager;

class PagesController extends AdminBaseController {
    /**
     * Info puslapių valdymas
     */
    public function pages(Request $request)
    {
        $gid = $request->User()->gid;

        $searchText = '';
        if (isset(explode('searchText=', $request->fullUrl())[1])) {
            $searchText = explode('&', explode('searchText=', $request->fullUrl())[1])[0];
        }

        if (strpos($request->path(), 'puslapiaiEN') !== false) {
            if (isset($searchText)) {
                $pages = Page::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', 'en')->where('editorG', '=', $gid)->orderBy('page.id', 'desc')->leftJoin('users_groups', 'page.editorG', '=', 'users_groups.id')->simplePaginate(10);
            } else {
                $pages = Page::where('editorG', '=', $gid)->orderBy('category', 'asc')->leftJoin('users_groups', 'page.editorG', '=', 'users_groups.id')->simplePaginate(20);
            }
        } elseif (strpos($request->path(), 'puslapiaiLT') !== false) {
            if (isset($searchText)) {
                $pages = Page::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', 'lt')->where('editorG', '=', $gid)->orderBy('page.id', 'desc')->leftJoin('users_groups', 'page.editorG', '=', 'users_groups.id')->simplePaginate(10);
            } else {
                $pages = Page::where('editorG', '=', $gid)->orderBy('category', 'asc')->leftJoin('users_groups', 'page.editorG', '=', 'users_groups.id')->simplePaginate(20);
            }
        } else {
            if (isset($searchText)) {
                $pages = Page::where('title', 'like', '%' . $request->searchText . '%')->orderBy('pages.id', 'desc')->where('editorG', '=', $gid)->leftJoin('users_groups', 'page.editorG', '=', 'users_groups.id')->simplePaginate(10);
            } else {
                $pages = Page::where('editorG', '=', $gid)->orderBy('category', 'asc')->leftJoin('users_groups', 'page.editorG', '=', 'users_groups.id')->simplePaginate(20);
            }
        }
    
        return view('pages.admin.pages', ['currentRoute' => $this->currentRoute, 'pages' => $pages, 'sessionInfo' => $request->User(), 'name' => null, 'searchText' => $searchText]);
    }

    public function getAddPage(Request $request)
    {
        $pagesCats = PagesCat::all();
        $pagesCatsShort = array();
        foreach ($pagesCats as $pagesCat) {
            $pagesCatsShort[$pagesCat['id']] = $pagesCat['name'];
        }

        return view('pages.admin.pageAdd', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'pagesCatsShort' => $pagesCatsShort]);
    }

    public function postAddPage(Request $request)
    {
        $rules = array(
            'title' => 'required|unique:page,title,NULL,id,editorG,' . $request->User()->gid,
            'permalink' => 'required',
            'category' => 'required',
            'text' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::to('/admin/puslapiai/prideti')->withInput()->withErrors(($validator));
        } else {
            if (substr($request->permalink, 0, 1) == '/') {
                $request->permalink = substr($request->permalink, 1);
            }

            $groupAlias = Users_group::where('id', '=', $request->User()->gid)->first();

            $page = new Page();
            $page->title = $request->title;
            $page->title_lt = $request->title_lt;
            $page->lang = $request->lang;

//            if ($groupAlias->alias != 'admin') {
//                $page->permalink = '/padalinys/' . $groupAlias->alias . '/' . $request->permalink;
//                $page->permalink_lt = '/padalinys/' . $groupAlias->alias . '/' . $request->permalink_lt;
//            } else {
            $page->permalink = $request->permalink;
            $page->permalink_lt = $request->permalink_lt;
//            }
            $page->text = $request->text;
            $page->mainInfo = $request->mainInfo;
            $page->category = $request->category;
            $page->editor = $request->User()->id;
            $page->editorG = $request->User()->gid;
            $page->save();
        }

        if ($request->lang == 'lt')
            return redirect('/admin/puslapiaiLT')->with('message', 'Puslapis sukurtas.');
        else
            return redirect('/admin/puslapiaiEN')->with('message', 'Puslapis sukurtas.');
    }

    /*auto complete*/
    public function getInfoPagesName(Request $request)
    {
        $results = array();
        $inputText = $request->input('term');
        $rezs = Page::select('id', 'title')->where('title', 'like', '%' . $inputText . '%')->take(10)->get();
        foreach ($rezs as $rez) {
            $results[] = ['id' => $rez->id, 'value' => $rez->title];
        }

        return Response::json($results);
    }

    public function getUpdatePage($permalink, Request $request)
    {
        $gid = $request->User()->gid;
        $userinfo = Users_group::where('id', '=', $gid)->first();

//        if ($gid == 1)
        $pageInfo = Page::where('permalink', 'like', $permalink)->where('editorG', '=', $gid)->first();
//        else
//            $pageInfo = Page::where('permalink', 'like', '/padalinys/' . $userinfo->alias . '/' . $permalink)->first();

        $pagesCats = PagesCat::all();
        $pagesCatsShort = array();
        foreach ($pagesCats as $pagesCat) {
            $pagesCatsShort[$pagesCat['id']] = $pagesCat['name'];
        }


        return view('pages.admin.pageEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'pageInfo' => $pageInfo, 'name' => null, 'pagesCatsShort' => $pagesCatsShort]);
    }

    public function postUpdatePage($permalink, Request $request)
    {
        $rules = array(
            'title' => 'required',
            'permalink' => 'required',
            'category' => 'required',
            'text' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::to('/admin/puslapiai/' . $permalink . '/redaguoti')->withInput()->withErrors(($validator));
        } else {
            $groupAlias = Users_group::where('id', '=', $request->User()->gid)->first();

//            if ($groupAlias->alias != 'admin') {
//                if (strpos($request->permalink, '/') !== false) {
//                    $permalink = $request->permalink;
//                    $permalink_lt = $request->permalink_lt;
//                } else {
//                    $permalink = '/padalinys/' . $groupAlias->alias . '/' . $request->permalink;
//                    $permalink_lt = '/padalinys/' . $groupAlias->alias . '/' . $request->permalink_lt;
//                }
//            } else {
            $permalink = $request->permalink;
            $permalink_lt = $request->permalink_lt;
//            }

            Page::where('id', '=', $request->id)->update([
                'title' => $request->title,
                'lang' => $request->lang,
                'permalink' => $permalink,
                'permalink_lt' => $permalink_lt,
                'category' => $request->category,
                'text' => $request->text,
                'mainInfo' => $request->mainInfo
            ]);

            if ($request->lang == 'lt')
                return redirect('/admin/puslapiaiLT')->with('message', 'Puslapio informacija atnaujinta.');
            else
                return redirect('/admin/puslapiaiEN')->with('message', 'Puslapio informacija atnaujinta.');
        }
    }

    public function deletePage(Request $request)
    {
        $itemId = $request->input('itemId');
        if (Page::where('id', '=', $itemId)->delete() == 1) {
            return response()->json('DELETED', 200);
        } else {
            return response()->json('NOT DELETED', 200);
        }
    }

    public function getChangeViewPage(Request $request)
    {
        $itemId = $request->input('itemId');
        $showArr = Page::select('disabled')->where('id', '=', $itemId)->first();
        if ($showArr->disabled == '1') {
            Page::where('id', '=', $itemId)->update(['disabled' => '0']);
        } else {
            Page::where('id', '=', $itemId)->update(['disabled' => '1']);
        }

        return "updated";
    }

    // public function getPadalinysPages(Request $request)
    // {
    //     $results = array();
    //     $inputText = $request->input('term');
    //     $rezs = Page::select('id', 'title')->where('editorG', '=', $request->User()->gid)->where('title', 'like', '%' . $inputText . '%')->take(10)->get();
    //     foreach ($rezs as $rez) {
    //         $results[] = ['id' => $rez->id, 'value' => $rez->title];
    //     }

    //     return Response::json($results);
    // }

    /**
     * Naujienų valdymas
     */
    public function news(Request $request)
    {
        $gid = $request->User()->gid;

        $searchText = '';

        if (isset(explode('searchText=', $request->fullUrl())[1])) {
            $searchText = explode('&', explode('searchText=', $request->fullUrl())[1])[0];
        }

        if ($gid > 3) {
            if (strpos($request->path(), 'naujienosEN') !== false) {
                if (isset($searchText)) {
                    $news = News::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', 'en')->where('publisher', '=', $gid)->orderBy('publish_time', 'desc')->simplePaginate(10);
                } else {
                    $news = News::where('publisher', '=', $gid)->orderBy('publish_time', 'desc')->simplePaginate(10);
                }
            } elseif (strpos($request->path(), 'naujienosLT') !== false) {
                if (isset($searchText)) {
                    $news = News::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', 'lt')->where('publisher', '=', $gid)->orderBy('publish_time', 'desc')->simplePaginate(10);
                } else {
                    $news = News::where('publisher', '=', $gid)->orderBy('publish_time', 'desc')->simplePaginate(10);
                }
            } else {
                if (isset($searchText)) {
                    $news = News::where('title', 'like', '%' . $request->searchText . '%')->where('publisher', '=', $gid)->orderBy('publish_time', 'desc')->simplePaginate(10);
                } else {
                    $news = News::where('publisher', '=', $gid)->orderBy('publish_time', 'desc')->simplePaginate(10);
                }
            }
        } else {
            if (strpos($request->path(), 'naujienosEN') !== false) {
                if (isset($searchText)) {
                    $news = News::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', 'en')->orderBy('publish_time', 'desc')->simplePaginate(10);
                } else {
                    $news = News::orderBy('publish_time', 'desc')->simplePaginate(10);
                }
            } elseif (strpos($request->path(), 'naujienosLT') !== false) {
                if (isset($searchText)) {
                    $news = News::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', 'lt')->orderBy('publish_time', 'desc')->simplePaginate(10);
                } else {
                    $news = News::orderBy('publish_time', 'desc')->simplePaginate(10);
                }
            } else {
                if (isset($searchText)) {
                    $news = News::where('title', 'like', '%' . $request->searchText . '%')->orderBy('publish_time', 'desc')->simplePaginate(10);
                } else {
                    $news = News::orderBy('publish_time', 'desc')->simplePaginate(10);
                }
            }
        }

        $newsCats = NewsCat::all();

        return view('pages.admin.news', ['currentRoute' => $this->currentRoute, 'news' => $news, 'sessionInfo' => $request->User(), 'newsCats' => $newsCats, 'name' => null, 'searchText' => $searchText]);
    }

    public function getAddNew(Request $request)
    {
        $news = News::orderBy('id', 'desc')->simplePaginate(10);
        $newsCats = NewsCat::all();
        $newsCatsShort = array();
        foreach ($newsCats as $newsCat) {
            $newsCatsShort[$newsCat['id']] = $newsCat['name'];
        }
        setlocale(LC_ALL, 'lt_LT.UTF-8');

        return view('pages.admin.newsAdd', ['currentRoute' => $this->currentRoute, 'news' => $news, 'sessionInfo' => $request->User(), 'newsCatsShort' => $newsCatsShort, 'name' => null]);
    }

    public function postAddNew(Request $request)
    {
        $rules = array(
            'title' => 'required|unique:news',
            'permalink' => 'required',
            'cat' => 'required',
            'short' => 'required',
            'text' => 'required',
            'image' => 'required'
        );

        $file = null;
        $fileName = "";
        $path = explode('/', $request->image);
        if ($request->image !== null) {
            $fileName = end($path);
            $manager = new ImageManager(array('driver' => 'imagick'));
            $frame_height = 0;
            $frame_width = 0;

            if ($request->frame_height != 0 && $request->frame_width != 0) {
                $frame_height = $request->frame_height;
                $frame_width = $manager->make($fileName)->width() / 1.1468;
                $scale_height = $manager->make($fileName)->height() / $frame_height;
                $scale_width = $manager->make($fileName)->width() / $frame_width;
                $manager->make($fileName)->crop(intval($scale_width * $request->width), intval($request->height * $scale_height),
                    intval($scale_width * $request->x), intval($scale_height * $request->y))->save($fileName);
            }
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/naujienos/prideti')->withInput()->withErrors(($validator));
        } else {
            if ($request->source == '') {
                $request->source = "VU SA";
            }

            $naujiena = new News();
            $naujiena->title = $request->title;
            $naujiena->cat = $request->cat;
            $naujiena->permalink = $request->permalink;
            $naujiena->permalink_lt = $request->permalink_lt;
            $naujiena->short = $request->short;
            $naujiena->text = $request->text;
            $naujiena->mainPoints = $request->mainPoints;
            $naujiena->image = $request->image;
            $naujiena->source = $request->source;
            $naujiena->imageAuthor = $request->imageAuthor;
            $naujiena->important = $request->important ?? 0;
            $naujiena->draft = $request->draft ?? 0;
            $naujiena->publish_time = $request->year . ':' . $request->month . ':' . $request->day . '-' . $request->hour . '-' . $request->minute;
            $naujiena->editor_time = date("Y-m-d H:i:s", time());
            $naujiena->tags = $request->tags;
            $naujiena->readMore = $request->readMore;
            $naujiena->publisher = $request->User()->gid;
            $naujiena->editor = $request->User()->id;
            $naujiena->save();
        }

        if ($request->lang == 'lt')
            return redirect('/admin/naujienosLT')->with('message', 'Naujiena sukurta.');
        else
            return redirect('/admin/naujienosEN')->with('message', 'Naujiena sukurta.');
    }

    public function deleteNew(Request $request)
    {
        $itemId = $request->input('itemId');
        if (News::where('id', '=', $itemId)->delete() == 1) {
            return response()->json('DELETED', 200);
        } else {
            return response()->json('NOT DELETED', 200);
        }
    }

    public function getUpdateNew($permalink, Request $request)
    {
        $newInfo = News::where('permalink', '=', $permalink)->first();
        if (strpos($newInfo->image, 'vusa.lt/') !== false)
            $newInfo->image = $newInfo->image;
        else
            $newInfo->image = 'https://vusa.lt/uploads/news/' . $newInfo->image;

        $newsCats = NewsCat::all();
        $newsCatsShort = array();
        foreach ($newsCats as $newsCat) {
            $newsCatsShort[$newsCat['id']] = $newsCat['name'];
        }

        setlocale(LC_ALL, 'lt_LT.UTF-8');

        return view('pages.admin.newsEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'newInfo' => $newInfo, 'name' => null, 'newsCatsShort' => $newsCatsShort]);
    }

    public function postUpdateNew($permalink, Request $request)
    {
        $rules = array(
            'title' => 'required',
            'permalink' => 'required',
            'cat' => 'required',
            'short' => 'required',
            'text' => 'required',
        );
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::to('/admin/naujienos/' . $permalink . '/redaguoti')->withInput()->withErrors(($validator));
        } else {
            if ($request->important === 'on') {
                $importantValue = '1';
            } else {
                $importantValue = '0';
            }

            if ($request->draft === 'on') {
                $draftValue = '1';
            } else {
                $draftValue = '0';
            }

            $request->publish_time = date("Y-m-d H:i:s", strtotime($request->year . '-' . $request->month . '-' . $request->day . ' ' . $request->hour . ':' . $request->minute));
            News::where('permalink', '=', $permalink)->update([
                'title' => $request->title,
                'title_lt' => $request->title_lt,
                'cat' => $request->cat,
                'permalink' => $request->permalink,
                'permalink_lt' => $request->permalink_lt,
                'short' => $request->short,
                'text' => $request->text,
                'source' => $request->source,
                'important' => $importantValue,
                'image' => $request->image,
                'imageAuthor' => $request->imageAuthor,
                'mainPoints' => $request->mainPoints,
                'publish_time' => $request->publish_time,
                'tags' => $request->tags,
                'readMore' => $request->readMore,
                'lang' => $request->lang,
                'draft' => $draftValue,
                'editor_time' => date("Y-m-d H:i:s", time())
            ]);

            if ($request->lang == 'lt')
                return redirect('/admin/naujienosLT')->with('message', 'Naujienos informacija atnaujinta.');
            else
                return redirect('/admin/naujienosEN')->with('message', 'Naujienos informacija atnaujinta.');
        }
    }

    /*auto complete*/
    public function getNewsName(Request $request)
    {
        $results = array();
        $inputText = $request->input('term');
        $rezs = News::select('id', 'title')->where('title', 'like', '%' . $inputText . '%')->take(10)->get();
        foreach ($rezs as $rez) {
            $results[] = ['id' => $rez->id, 'value' => $rez->title];
        }

        return Response::json($results);
    }

    public function postSearchNews(Request $request)
    {
        return view('pages.admin.news', ['currentRoute' => $this->currentRoute, 'news' => $this->searchRez, 'sessionInfo' => $request->User(), 'name' => null]);
    }

}