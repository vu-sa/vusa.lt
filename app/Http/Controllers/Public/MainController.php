<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\IcalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MainController extends PublicController
{
    public function publicAllEventCalendar()
    {
        return response((new IcalendarService)->get())
            ->header('Content-Type', 'text/calendar; charset=utf-8');
    }

    public function getMainNews()
    {
        // get last 4 news by publishing date
        $tenant = Tenant::where('shortname', '=', 'VU SA')->first();
        $mainNews = News::select('title', 'short', 'image')->where([['tenant_id', '=', $tenant->id], ['draft', '=', 0]])->orderBy('publish_time', 'desc')->take(4)->get();

        return response()->json($mainNews, 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);
        //
    }

    public function search()
    {
        // get search query
        $search = request()->data['input'];

        $calendar = Calendar::search($search)->orderBy('date', 'desc')->take(5)->get()->only(['id', 'title', 'date', 'permalink', 'lang']);

        $news = News::search($search)->orderBy('publish_time', 'desc')->take(5)->get()->only(['id', 'title', 'publish_time', 'image', 'permalink', 'lang']);

        $pages = Page::search($search)->orderBy('created_at', 'desc')->take(5)->get()->only(['id', 'title', 'permalink', 'lang']);

        $documents = Document::search($search)->orderBy('document_date', 'desc')->take(5)->get()->only(['id', 'name', 'title', 'document_date', 'anonymous_url', 'language', 'content_type', 'created_at', 'summary']);

        return back()->with('search', ['calendar' => $calendar, 'news' => $news, 'pages' => $pages, 'documents' => $documents]);
    }

    public function sendFeedback(Request $request)
    {
        $data = $request->all();

        Mail::to('it@vusa.lt')->queue(new \App\Mail\FeedbackMail($data['feedback'], auth()->user(), $data['href'], $data['selectedText']));

        return back()->with('success', 'Ačiū už atsiliepimą!');

    }
}
