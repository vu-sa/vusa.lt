<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\News;
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

    public function sendFeedback(Request $request)
    {
        $data = $request->all();

        Mail::to('it@vusa.lt')->queue(new \App\Mail\FeedbackMail($data['feedback'], auth()->user(), $data['href'], $data['selectedText']));

        return back()->with('success', 'Ačiū už atsiliepimą!');

    }
}
