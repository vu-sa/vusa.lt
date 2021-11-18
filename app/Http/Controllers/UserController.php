<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Contact;
use App\Models\MainPage;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Page;
use App\Models\Saziningai_people;
use App\Models\Users_group;
use App\Models\Saziningai;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class UserController extends BaseController
{
    // Regulates, how many main vusa.lt news are shown
    private $newsSlide = 4;

    private $banners;
    private $navLevel1;
    private $navLevel2;
    private $language;
    private $currentRoute;

    private $outputArray;

    // Is run everytime when a user site is accessed
    public function __construct(Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);

        if (substr(request()->path(), 0, 3) == "en/" || request()->path() == "en") {
            App::setLocale('en');
        }

        $this->language = Lang::locale();

        // Create output basic array
        $this->navLevel1 = Navigation::where('pid', '=', 0)->where('show', '=', '1')->where('lang', '=', $this->language)->orderBy('order')->get();
        $this->navLevel2 = Navigation::where('pid', '!=', 0)->where('show', '=', '1')->where('lang', '=', $this->language)->orderBy('order')->get();
        $this->banners = Banner::where('hide', '=', '0')->where('editorG', '=', '1')->inRandomOrder()->get();
        $this->currentRoute = $request->path();
        $this->appUrl = config('app.url');

        Log::debug("currentRoute is " . $this->currentRoute);

        // Domain alias in vusa.lt is "vusa", in naujas.vusa.lt is "naujas" and so on...
        $this->domainAlias = explode('.', $request->server()['HTTP_HOST']);

        switch(count($this->domainAlias)) {
            case 2:
                $this->navbarRoot = '';
                break;
            case 3:
                if ($this->domainAlias[0] == 'naujas') {
                    $this->navbarRoot = ''; 
                    break;
                } elseif (strpos($this->domainAlias[2], 'testas') === 0) {
                    $this->navbarRoot = 'http://vusa.testas:8000';
                    break;
                } else {
                    $this->navbarRoot = 'https://vusa.lt';
                    break;
                }
            }

        if (!in_array($this->domainAlias[0], ['naujas', 'vusa'])) {
            $padalinysAlias = "vusa" . $this->domainAlias[0];
            $this->en = Padalinys::where('alias', '=', $padalinysAlias)->first()['en'];
        } else {
            $this->en = 0;
        }

        $this->padaliniaiEn = Padalinys::where('en', '=', 1)->orderby('alias')->get();

        Log::debug('firstDomainAlias is ' . $this->domainAlias[0]);

        $this->outputArray = [
            'currentRoute' => $this->currentRoute, 'banners' => $this->banners, 'navLevel1' => $this->navLevel1, 
            'navLevel2' => $this->navLevel2, 'navLevel3' => $this->navLevel2, 'navLevel4' => $this->navLevel2, 'navbarRoot' => $this->navbarRoot, 'en' => $this->en, 'padaliniaiEn' => $this->padaliniaiEn, 'domainAlias' => $this->domainAlias, 'appUrl' => $this->appUrl
        ];
    }


    // url: "vusa.lt/", "x.vusa.lt/"
    public function index()
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);

        $topNews = News::where('important', '=', '1')->where('lang', '=', $this->language)->where('draft', '=', '0')->orderBy('publish_time', 'desc')->take($this->newsSlide)->get();
        $events = Calendar::where('date', 'like', date('Y-m') . '%')->get();
        $agenda = Agenda::where('date', '>=', date('Y-m-d') . '%')->orderby('date')->limit(3)->get();
        $mainPageInfo = MainPage::where('groupID', '=', '1')->get();

        $index = 0;
        foreach ($mainPageInfo as $mainPageInfoItem) {
            $mainPageInfo[$index]->newsInfo = News::where('id', '=', $mainPageInfoItem['newsID'])->get()->first();
            $index += 1;
        }

        $this->outputArray['topNews'] = $topNews;
        $this->outputArray['agenda'] = $agenda;
        $this->outputArray['events'] = $events;
        $this->outputArray['currentYearMonth'] = date('Y-m');
        $this->outputArray['mainPageInfo'] = $mainPageInfo;
        
        return view('pages.user.main')->with($this->outputArray);

    }

    public function getContacts($locale, $contactsLocale, $name)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        if (in_array($this->domainAlias[0], array('vusa', 'naujas'))) {

            $this->domainAlias[0] = 'vusa';

            $contactGroupDescription = Contact::select('infoText')->where('groupname', 'like', 'aprasymas')->where('grouptitle', 'like', $name)->first();

            if ($name == 'centrinis-biuras') {
                $this->outputArray['contacts'] = Contact::where('groupname', 'like', $name)->orderBy('contactOrder')->get();
                $this->outputArray['contactGroupDescription'] = $contactGroupDescription['infoText'];

                return view('pages.user.contactsCB', $this->outputArray);
            }

            if ($name == 'central-office') {
                $this->outputArray['contacts'] = Contact::where('groupname', 'like', $name)->orderBy('contactOrder')->get();
                $this->outputArray['contactGroupDescription'] = $contactGroupDescription['infoText'];

                return view('pages.user.contactsCBEN', $this->outputArray);
            }

            if ($name == 'padaliniai') {
                $this->outputArray['contacts'] = Contact::where('groupname', 'like', $name)->orderBy('name_short')->get();

                return view('pages.user.contactsPadaliniai', $this->outputArray);
            }

            if ($name == 'taryba') {
                $this->outputArray['contacts'] = Contact::where('groupname', 'like', $name)->orderBy('name_short')->get();
                
                return view('pages.user.contactsTaryba', $this->outputArray);
            }

            if ($name == 'parlamentas') {

                $this->outputArray['contacts'] = Contact::where('groupname', 'like', $name)->orderBy('contactOrder')->get();
                $this->outputArray['parl_pirm'] = Contact::where('groupname', 'parl-pirm')->first();
                $this->outputArray['parlDBs'] = Contact::where('groupname', 'parlamento-darbo-grupes')->get();
                $this->outputArray['contactGroupDescription'] = $contactGroupDescription['infoText'];
                
                return view('pages.user.contactsParlamentas', $this->outputArray);
            }

            if ($name == 'institucinio-stiprinimo-fondas') {
                $this->outputArray['contacts'] = Contact::where('groupname', 'like', 'stiprinimas')->get();
                $this->outputArray['contactGroupDescription'] = $contactGroupDescription['infoText'];

                return view('pages.user.contactsISF', $this->outputArray);
            }

            if ($name == 'studentu-atstovai') {
                $this->outputArray['contacts'] = Contact::where('groupname', 'like', $name)->get();
                $this->outputArray['contactsGroups'] = Contact::select('grouptitle')->where('groupname', 'like', $name)->distinct()->get();

                return view('pages.user.contactsStudentuAtstovai', $this->outputArray);
            }

            if ($name == 'programos-klubai-projektai') {
                $this->outputArray['contacts'] = Contact::where('groupname', 'like', $name)->get();

                return view('pages.user.contactsProgramos', $this->outputArray);
            }

            if ($name == 'revizijos-komisija') {
                $this->outputArray['contacts'] = Contact::where('groupname', 'like', 'revizija')->get();

                return view('pages.user.contactsRevizija', $this->outputArray);
            }
        }
        /**
         * Padalinių kontaktai
         */
        else if ($this->domainAlias[1] == 'vusa') {
            $alias = 'vusa' . $this->domainAlias[0];
            $padalinys = Padalinys::where('alias', '=', $alias)->first();
            $userGroup = Users_group::where('alias', '=', $alias)->first();

            if ($name == 'taryba') {
                $name = 'padalinio-taryba';
                $this->outputArray['title'] = $padalinys->shortname . ' ' . 'taryba';
            }

            if (in_array($name, ['biuras', 'koordinatoriai', 'coordinators'])) {
                
                $name = $locale == 'en' ? 'padalinio-biuras-en' : 'padalinio-biuras';
                if (substr($padalinys->shortname, 6) == 'MIF')
                $this->outputArray['title'] = __('VU SA MIF') . ' biuras';
                else
                $this->outputArray['title'] = __($padalinys->shortname) . ' ' . lcfirst(__('Koordinatoriai'));
            }

            if ($name == 'studentu-atstovai') {
                $name = 'padalinio-studentu-atstovai';
                $this->outputArray['title'] = $padalinys->shortname . ' ' . 'studentų atstovai';
            }

            if (in_array($name, ['kuratoriai', 'mentors'])) {
                $name = $locale == 'en' ? 'padalinio-kuratoriai-en' : 'padalinio-kuratoriai';
                if (substr($padalinys->shortname, 6) == 'FilF')
                $this->outputArray['title'] = 'VU FLF kuratoriai';
                else
                $this->outputArray['title'] = 'VU ' . substr($padalinys->shortname, 6) . ' ' . lcfirst(__('Kuratoriai'));
            }

            // TODO: Use 'lang' column in future!

            $this->outputArray['contacts'] = Contact::where('groupname', 'like', $name)->where('contactGroup', '=', $userGroup->id)->orderBy('contactOrder')->get();

            $contactGroupDescription = Contact::where('groupname', 'like', 'aprasymas-padalinys')->where('grouptitle', 'like', $name)->where('contactGroup', '=', $userGroup->id)->first();
            $this->outputArray['contactGroupDescription'] = $contactGroupDescription['infoText'] ?? "";

            $this->outputArray['contactGroupPhoto'] = $contactGroupDescription['image'] ?? "";

            return view('pages.user.contactsPadaliniaiKomanda', $this->outputArray);
        }

        $this->outputArray['contacts'] = null;

        return response()->view('errors.404', $this->outputArray, 404);
    }

    public function getInfoPage($locale, $permalink = null)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        Log::info('Permalink: ' . $permalink);
        
        if ($this->domainAlias[0] == 'vusa' || $this->domainAlias[0] . $this->domainAlias[1] == 'naujasvusa') {
            $page = Page::where('permalink', '=', $permalink)->where('lang', '=', $this->language)->where('editorG', '=', '1')->first();
        } else if ($this->domainAlias[1] == 'vusa') {
            $gid = Users_group::where('alias', 'like', 'vusa' . $this->domainAlias[0])->first();
            Log::info($gid);
            $page = Page::where('permalink', 'like', '%' . $permalink)->where('lang', '=', $this->language)->where('editorG', '=', $gid->id)->first();
        } 

        if ($page == null) {

            $this->outputArray['title'] = 'Puslapis nerastas';
            return response()->view('errors.404', $this->outputArray, 404);
        }

        $this->outputArray['page'] = $page;
        return view('pages.user.page', $this->outputArray);

    }

    public function getDukPage()
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        return view('pages.user.pageDuk', $this->outputArray);
    }

    public function getPkpPage()
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $this->outputArray['page'] = Page::where('permalink', '=', 'programos-klubai-projektai')->first();

        preg_match_all("'<h3>(.*?)</h3>'si", $this->outputArray['page']->text, $match);
        $this->outputArray['pkpTitle'] = $match[1];
        preg_match_all("'<p>(.*?)</p>'si", $this->outputArray['page']->text, $match);
        $this->outputArray['pkpDescription'] = $match[1];
        preg_match_all("'<a (.*?)</a>'si", $this->outputArray['page']->text, $match);
        $pkpLinks = $match[1];

        for ($i = 0; $i < sizeof($pkpLinks); $i++) {
            $pkpLinks[$i] = substr($pkpLinks[$i], 6);
            $pkpLinks[$i] = substr($pkpLinks[$i], 0, strpos($pkpLinks[$i], '"'));
            $pkpLinks[$i] = str_replace('../../../', '', $pkpLinks[$i]);
        }

        $this->outputArray['pkpLinks'] = $pkpLinks;

        return view('pages.user.pagePKP', $this->outputArray);
    }

    public function getNew($locale, $newsLocale, $permalink)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        if ($this->domainAlias[0] == 'vusa' || $this->domainAlias[0] . $this->domainAlias[1] == 'naujasvusa') {
            $news = News::where('permalink', '=', $permalink)->where('lang', '=', $this->language)->first();
        } else if ($this->domainAlias[1] == 'vusa') {
            $groupID = Users_group::where('alias', '=', 'vusa' . $this->domainAlias[0])->first();
            $news = News::where('permalink', '=', $permalink)->where('publisher', '=', $groupID->id)->where('lang', '=', $this->language)->first();
        }

        if ($news == null) {

            $this->outputArray['title'] = 'Puslapis nerastas';
            return response()->view('errors.404', $this->outputArray, 404);

        }

        $this->outputArray['news'] = $news;
        $this->outputArray['url'] = $url;
        $this->outputArray['selectedNewPermalink'] = $permalink;
        
        return view('pages.user.naujiena', $this->outputArray);
    }

    public function getMainNews()
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $topNews = News::select('title', 'short', 'image')->where('important', '=', '1')->where('draft', '=', '0')->where('lang', '=', $this->language)->orderBy('publish_time', 'desc')->take(4)->get();
        return response()->json($topNews, 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);
    }

    //  url: naujiena/archyvas, p: naujienos, naujiena/archyvas
    public function getNewsArchive()
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        //  Nuoroda į bendrąjį naujienų archvyą, kai vusa.lt/... arba naujas.vusa.lt/...

        if (in_array($this->domainAlias[0], array('vusa', 'naujas'))) {
            $news = News::orderBy('publish_time', 'desc')->where('lang', '=', $this->language)->where('draft', '=', 0)->simplePaginate(10);

            for ($i = 0; $i < sizeof($news); $i++) {
                if ($news[$i]->publisher > 3) {
                    $userGroup = Users_group::where('id', '=', $news[$i]->publisher)->first();
                    if ($news[$i]->tags == '')
                        $news[$i]->tags .= $userGroup->descr;
                    else
                        $news[$i]->tags .= '; ' . $userGroup->descr;
                }
            }
        } else {

            //  Nuoroda į bendrąjį naujienų archvyą padaliniams

            if ($this->domainAlias[1] = 'vusa')
                $userGroup = 'vusa' . $this->domainAlias[0];

            $gid = Users_group::where('alias', '=', $userGroup)
            ->first();

            $news = News::orderBy('publish_time', 'desc')
            ->where('lang', '=', $this->language)
            ->where('publisher', '=', $gid->id)
            ->where('draft', '=', 0)
            ->simplePaginate(10);

            for ($i = 0; $i < sizeof($news); $i++) {
                $userGroup = Users_group::where('id', '=', $news[$i]->publisher)
                ->first();
                
                if ($news[$i]->tags == '')
                    $news[$i]->tags .= $userGroup->descr;
                else
                    $news[$i]->tags .= '; ' . $userGroup->descr;
            }
        }

        $this->outputArray['news'] = $news;
        $this->outputArray['searchKeywords'] = null;

        return view('pages.user.newsArchive', $this->outputArray);
    }

    public function postNewsArchive(Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $gids = Users_group::where('id', '>', 3)->get();

        if ($request->searchText != '' && $request->dateFrom != '' && $request->dateTo != '') {
            $dateFrom = explode('-', str_replace('/', '-', $request->dateFrom));
            $dateFrom = $dateFrom[2] . '-' . $dateFrom[0] . '-' . $dateFrom[1];
            $dateTo = explode('-', str_replace('/', '-', $request->dateTo));
            $dateTo = $dateTo[2] . '-' . $dateTo[0] . '-' . $dateTo[1];
            $news = News::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', $this->language)->whereBetween('publish_time', array($dateFrom, $dateTo))->orderBy('publish_time', 'desc')->simplePaginate(30);
        } elseif ($request->searchText != '' && $request->dateTo != '') {
            $dateTo = explode('-', str_replace('/', '-', $request->dateTo));
            $dateTo = $dateTo[2] . '-' . $dateTo[0] . '-' . $dateTo[1];
            $news = News::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', $this->language)->where('publish_time', '<', $dateTo)->orderBy('publish_time', 'desc')->simplePaginate(10);
        } elseif ($request->searchText != '' && $request->dateFrom != '') {
            $dateFrom = explode('-', str_replace('/', '-', $request->dateFrom));
            $dateFrom = $dateFrom[2] . '-' . $dateFrom[0] . '-' . $dateFrom[1];
            $news = News::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', $this->language)->where('publish_time', '>', $dateFrom)->orderBy('publish_time', 'desc')->simplePaginate(10);
        } elseif ($request->searchText != '' && $request->dateFrom == '' && $request->dateTo == '') {
            $news = News::where('title', 'like', '%' . $request->searchText . '%')->where('lang', '=', $this->language)->orderBy('publish_time', 'desc')->simplePaginate(10);
        } else if ($request->searchText == '' && $request->dateFrom != '' && $request->dateTo != '') {
            $dateFrom = explode('-', str_replace('/', '-', $request->dateFrom));
            $dateFrom = $dateFrom[2] . '-' . $dateFrom[0] . '-' . $dateFrom[1];
            $dateTo = explode('-', str_replace('/', '-', $request->dateTo));
            $dateTo = $dateTo[2] . '-' . $dateTo[0] . '-' . $dateTo[1];
            $news = News::whereBetween('publish_time', array($dateFrom, $dateTo))->where('lang', '=', $this->language)->orderBy('publish_time', 'desc')->simplePaginate(10);
        } else if ($request->searchText == '' && $request->dateFrom == '' && $request->dateTo != '') {
            $dateTo = explode('-', str_replace('/', '-', $request->dateTo));
            $dateTo = $dateTo[2] . '-' . $dateTo[0] . '-' . $dateTo[1];
            $news = News::where('publish_time', '<', $dateTo)->where('lang', '=', $this->language)->orderBy('publish_time', 'desc')->simplePaginate(10);
        } else if ($request->searchText == '' && $request->dateFrom != '' && $request->dateTo == '') {
            $dateFrom = explode('-', str_replace('/', '-', $request->dateFrom));
            $dateFrom = $dateFrom[2] . '-' . $dateFrom[0] . '-' . $dateFrom[1];
            $news = News::where('publish_time', '>', $dateFrom)->where('lang', '=', $this->language)->orderBy('id', 'desc')->simplePaginate(10);
        } else if ($request->searchText == '' && $request->dateFrom == '' && $request->dateTo == '') {
            $news = News::orderBy('publish_time', 'desc')->where('lang', '=', $this->language)->simplePaginate(10);
        } else {
            $news = null;
        }

        foreach ($request->request as $item2) {
            $item[] = $item2;
        }

        $size = 0;
        if ($news != null) {
            foreach ($news as $new) {
                $size++;
            }
        }

        $this->outputArray['searchKeywords'] = $item;
        $this->outputArray['size'] = $size;
        $this->outputArray['gids'] = $gids;
        $this->outputArray['news'] = $news;

        return view('pages.user.newsArchive', $this->outputArray);
    }

    public function getSearchByTag($tag)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $news = News::orderBy('id', 'desc')->where('tags', 'like', '%' . $tag . '%')->where('lang', '=', $this->language)->simplePaginate(10);

        // ? Ką daro ši funkcija? Kodėl jinai aktyvuojama, kai nėra naujienų? Išsiaiškinti!
        // * Kai nebuvo tikrinamas `$news == null`, $news tikrinimas išmesdavo HTTP 500.

        if (sizeof($news) == 0) {
            
            return response()->view('errors.404', ['currentRoute' => $this->currentRoute, 'banners' => $this->banners, 'navLevel1' => $this->navLevel1, 'navLevel2' => $this->navLevel2, 'navLevel3' => $this->navLevel2, 'navLevel4' =>
            $this->navLevel2, 'title' => 'Puslapis nerastas'], 404);
            
            // $userGroup = Users_group::where('descr', '=', $tag)->first();
            // $news = News::orderBy('id', 'desc')->where('publisher', '=', $userGroup->id)->where('lang', '=', $this->language)->simplePaginate(10);

            // for ($i = 0; $i < sizeof($news); $i++) {
            //     if ($news[$i]->publisher > 3) {
            //         $userGroup = Users_group::where('id', '=', $news[$i]->publisher)->first();
            //         if ($news[$i]->tags == '')
            //             $news[$i]->tags .= $userGroup->descr;
            //         else
            //             $news[$i]->tags .= '; ' . $userGroup->descr;
            //     }
            // }
        }

        $this->outputArray['news'] = $news;
        $this->outputArray['searchKeywords'] = null;
        
        return view('pages.user.newsSearchByTag', $this->outputArray);
    }

    public function getMainPageSearch(Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $text = $request->searchText;

        $this->outputArray['searchRezNews'] = News::where('text', 'like', '%' . $text . '%')->orWhere('title', 'like', '%' . $text . '%')->where('lang', '=', $this->language)->orderBy('id', 'desc')->get();

        $this->outputArray['searchRezPages'] = Page::orderBy('id', 'desc')->where('text', 'like', '%' . $text . '%')->where('disabled', 'like', '0')->orWhere('title', 'like', '%' . $text . '%')->where('lang', '=', $this->language)->get();

        $this->outputArray['searchKeywords'] = null;

        return view('pages.user.mainPageResult', $this->outputArray);
    }

    public function getEvents(Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $inputYear = $request->input('year');
        $inputMonth = $request->input('month');

        if ($inputMonth < 10) {
            $inputMonth = '0' . $inputMonth;
        }

        $events = Calendar::select('id', 'date', 'title', 'classname', 'descr')->where('date', 'like', $inputYear . '-' . $inputMonth . '%')->orderBy('date', 'asc')->get();
        $agendas = Agenda::select('id', 'date', 'title', 'classname', 'description')->where('date', 'like', $inputYear . '-' . $inputMonth . '%')->orderBy('date', 'asc')->get();


        $commonArray = array();
        $i = 0;
        foreach ($agendas as $agenda) {
            $commonArray[$i++] = $agenda;
        }

        foreach ($events as $event) {
            $commonArray[$i++] = $event;
        }

        $tempCommons = $commonArray;
        $commonArray = null;

        $index = 0;
        foreach ($tempCommons as $tempCommon) {
            if (isset($commonArray[$tempCommon['date']])) {
                $commonArray[$tempCommon['date']]['title'] .= '|||' . $tempCommon['title'];
                $commonArray[$tempCommon['date']]['classname'] .= '|||' . $tempCommon['classname'];
            } else {
                $commonArray[$tempCommon['date']]['date'] = $tempCommon['date'];
                $commonArray[$tempCommon['date']]['title'] = $tempCommon['title'];
                $commonArray[$tempCommon['date']]['classname'] = $tempCommon['classname'];
                $commonArray[$tempCommon['date']]['badge'] = $tempCommon['badge'];
                $index++;
            }
        }

        return Response::json($commonArray);
    }

    public function getAgenda()
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $this->outputArray['agendas_today'] = Agenda::where('date', '=', date('Y-m-d') . '%')->get();
        $this->outputArray['agendas_comming'] = Agenda::where('date', '>', date('Y-m-d') . '%')->orderby('date', 'asc')->get();

        $this->outputArray['agendas_past'] = Agenda::where('date', '<', date('Y-m-d') . '%')->orderby('date', 'desc')->simplePaginate(10);

        return view('pages.user.agenda', $this->outputArray);
    }

    public function getAgendaAjax(Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $pageID = $request->input('page');
        $agenda_past = Agenda::where('date', '<', date('Y-m-d') . '%')->orderby('date', 'desc')->simplePaginate(10);
        return $agenda_past;
    }

    public function getPadalinysPage(Request $request)
    //    public function getPadalinysPage($alias, Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);

        $alias = 'vusa' . $this->domainAlias[0];

        $gid = Users_group::select('id')->where('alias', '=', $alias)->first();
        
        // TODO: kažką čia su banneriais padaryti
        $this->outputArray['banners'] = Banner::where('editorG', '=', $gid->id)->where('hide', '=', '0')->orWhere('editorG', '=', '1')->where('hide', '=', '0')->orderBy('editorG', 'desc')->get();
        $this->outputArray['news'] = News::where('lang', '=', $this->language)->where('publisher', '=', $gid->id)->orderBy('publish_time', 'desc')->limit(3)->get();
        $this->outputArray['pages'] = Page::where('editorG', '=', $gid->id)->get();
        $this->outputArray['contacts'] = Contact::where('contactGroup', '=', $gid->id)->get();
        $this->outputArray['padalinys'] = Padalinys::where('alias', '=', $alias)->first();
        $this->outputArray['menuItemsSide'] = MainPage::where('groupID', '=', $gid->id)->where('position', '=', 'sideItem')->where('lang', '=', $this->language)->orderBy('orderID', 'asc')->get();
        $this->outputArray['menuItemsBottom'] = MainPage::where('groupID', '=', $gid->id)->where('position', '=', 'bottomItem')->where('lang', '=', $this->language)->orderBy('orderID', 'asc')->get();
        $this->outputArray['additionalInfo'] = MainPage::where('groupID', '=', $gid->id)->where('position', '=', 'additionalInfo')->first();
        $this->outputArray['hasKoordinatoriaiMenuSide'] = false;
        $this->outputArray['hasKuratoriaiMenuSide'] = false;

        for ($i = 0; $i < count($this->outputArray['menuItemsSide']); $i++) { 
            
            if($this->outputArray['hasKoordinatoriaiMenuSide'] == false && $this->outputArray['menuItemsSide'][$i]['link'] == '/lt/kontaktai/koordinatoriai') {
                $this->outputArray['hasKoordinatoriaiMenuSide'] = true;
            }
            if($this->outputArray['hasKuratoriaiMenuSide'] == false && $this->outputArray['menuItemsSide'][$i]['link'] == '/lt/kontaktai/kuratoriai') {
                $this->outputArray['hasKuratoriaiMenuSide'] = true;
            }

        }

        if ($this->outputArray['padalinys'] == null) {
            
            $this->outputArray['title'] = 'Puslapis nerastas';

            return response()->view('errors.404', $this->outputArray, 404);
        }

        return view('pages.user.padalinysHome', $this->outputArray);
    }

    public function page404()
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $this->outputArray['title'] = 'Puslapis nerastas';
        
        return response()->view('errors.404', $this->outputArray, 404);
    }

    public function getExamRegistration()
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        return view('pages.user.examRegistracion', $this->outputArray);
    }

    public function postExamRegistration(Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $rules = array(
            'name' => 'required',
            'contact' => 'required',
            'exam' => 'required',
            'padalinys' => 'required',
            'place' => 'required',
            'time' => 'required|date|after:+3 days',
            'duration' => 'required',
            'subject_name' => 'required',
            'count' => 'required|integer',
            'acceptGDPR' => 'accepted',
            'acceptDataManagement' => 'accepted'
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 200);
        } else {
            $saziningai = new Saziningai();
            if ($request->name == '')
                $saziningai->name = 'Anonimas';
            else
                $saziningai->name = $request->name;

            $saziningai->uuid = bin2hex(random_bytes(15));
            $saziningai->contact = $request->contact;
            $saziningai->exam = $request->exam;
            $saziningai->padalinys = $request->padalinys;
            $saziningai->place = $request->place;
            $saziningai->time = $request->time . ' | ' . $request->time2 . ' | ' . $request->time3 . ' | ' . $request->time4;
            $saziningai->duration = $request->duration;
            $saziningai->subject_name = $request->subject_name;
            $saziningai->count = $request->count;
            $saziningai->save();
        }
        return response()->json('OK', 200);
    }

    public function getExamRegistrationList()
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $atsiskaitymai = Saziningai::where('time', '>', date('Y-m-d'))->orderBy('time')->get();

        $i = 0;
        foreach ($atsiskaitymai as $atsiskaitymas) {
            $flows = 0;
            $timeArr = explode('|', $atsiskaitymas->time);
            foreach ($timeArr as $time)
                if (strlen($time) > 2)
                    $flows++;

            $registed = array_fill(1, $flows, 0);
            for ($flow = 1; $flow <= $flows; $flow++) {
                $registed[$flow] = Saziningai_people::where('exam_uuid', '=', $atsiskaitymas->uuid)->where('flow', '=', $flow)->where('status_p', '=', 'atvyko')->get()->count();
            }
            $atsiskaitymai[$i]->students_registered = $registed;
            $i++;
        }

        $this->outputArray['atsiskaitymai'] = $atsiskaitymai;

        return view('pages.user.examRegistracionList', $this->outputArray);
    }

    public function getExamRegistrationAjax(Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $padalinys = $request->input('padalinys');
        $pavadinimas = $request->input('pavadinimas');
        $atsiskaitymai = null;

        if ($padalinys != null && $pavadinimas != null)
            $atsiskaitymai = Saziningai::where('time', '>', date('Y-m-d'))->orderBy('time')->where('padalinys', 'like', $padalinys)->where('subject_name', 'like', '%' . $pavadinimas . '%')->get();
        elseif ($padalinys != null)
            $atsiskaitymai = Saziningai::where('time', '>', date('Y-m-d'))->orderBy('time')->where('padalinys', 'like', $padalinys)->get();
        elseif ($pavadinimas != null)
            $atsiskaitymai = Saziningai::where('time', '>', date('Y-m-d'))->orderBy('time')->where('subject_name', 'like', '%' . $pavadinimas . '%')->get();


        return response()->json($atsiskaitymai, 200, ['Content-type' => 'application/json; charset=utf-8', 0], JSON_UNESCAPED_UNICODE);
    }

    public function getPersonRegistration(Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $examuuid = $request->input('uuid');

        $this->outputArray['atsiskaitymas'] = Saziningai::where('uuid', '=', $examuuid)->first();

        return view('pages.user.peopleRegistracion', $this->outputArray);
    }

    public function postPersonRegistration(Request $request)
    {
        Log::debug('Start of ' . __METHOD__ . 'function in ' . __FILE__);
        
        $rules = array(
            'uuid' => 'required',
            'name_p' => 'required',
            'padalinys' => 'required',
            'flow' => 'required',
            'contact_p' => 'required',
            'acceptGDPR' => 'accepted',
            'acceptDataManagement' => 'accepted'
        );

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 200);
        } else {
            $saziningai_people = new Saziningai_people();
            $saziningai_people->exam_uuid = $request->uuid;
            $saziningai_people->name_p = $request->name_p;
            $saziningai_people->padalinys_p = $request->padalinys;
            $saziningai_people->contact_p = $request->contact_p;
            $saziningai_people->flow = $request->flow;
            $saziningai_people->status_p = 'atvyko';
            $saziningai_people->save();
        }
        return response()->json('OK', 200);
    }
}
