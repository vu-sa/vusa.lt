<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Mail\ConfirmMemberRegistration;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MemberRegistered;
use App\Services\IcalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class MainController extends PublicController
{
    public function publicAllEventCalendar()
    {
        return response((new IcalendarService)->get())
            ->header('Content-Type', 'text/calendar; charset=utf-8');
    }

    // TODO: redo

    /*public function storeMemberRegistration()*/
    /*{*/
    /*    // store registration*/
    /*    // 1 registration is to MIF camp, 2 is for VU SA and PKP members*/
    /**/
    /*    $data = request()->all();*/
    /*    if ($data['registrationForm'] == 3) {*/
    /*        $this->storeRegistration(RegistrationForm::find(3));*/
    /**/
    /*        return;*/
    /*    } else {*/
    /*        $this->storeRegistration(RegistrationForm::find(2));*/
    /*        $registerLocation = new Tenant;*/
    /*        $chairPerson = new User;*/
    /**/
    /*        // if whereToRegister is int, then it is a tenant id*/
    /*        if (is_int($data['whereToRegister'])) {*/
    /*            $registerTenant = Tenant::find($data['whereToRegister']);*/
    /*            $registerLocation = __($registerTenant->fullname);*/
    /*            $chairDuty = $registerTenant->duties()->whereHas('types', function ($query) {*/
    /*                $query->where('slug', 'pirmininkas');*/
    /*            })->first();*/
    /*            $chairPerson = $chairDuty->current_users->first();*/
    /*            $chairEmail = $chairDuty->email;*/
    /*        } else {*/
    /*            switch ($data['whereToRegister']) {*/
    /*                case 'hema':*/
    /*                    $registerLocation = 'HEMA ('.__('Istorinių Europos kovos menų klubas').')';*/
    /*                    $chairEmail = 'hema@vusa.lt';*/
    /*                    break;*/
    /**/
    /*                case 'jek':*/
    /*                    $registerLocation = 'VU '.__('Jaunųjų energetikų klubas');*/
    /*                    $chairEmail = 'vujek@jek.lt';*/
    /*                    break;*/
    /**/
    /*                default:*/
    /*                    abort(500);*/
    /*                    break;*/
    /*            }*/
    /*        }*/
    /**/
    /*        // send mail to the registered person*/
    /*        Mail::to($data['email'])->send(new ConfirmMemberRegistration($data, $registerLocation, $chairPerson, $chairEmail));*/
    /*        Notification::send($chairPerson, new MemberRegistered($data, $registerLocation, $chairEmail));*/
    /*    }*/
    /*}*/
    /**/
    /*public function storeRegistration(RegistrationForm $registrationForm)*/
    /*{*/
    /*    $registration = new Registration;*/
    /*    $registration->data = request()->except('registrationForm', 'tenant');*/
    /*    $registration->registration_form_id = $registrationForm->id;*/
    /*    $registration->save();*/
    /*}*/

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

        $calendar = Calendar::search($search)->orderBy('date', 'desc')->take(5)->get(['id', 'title', 'date', 'permalink', 'lang']);

        $news = News::search($search)->orderBy('publish_time', 'desc')->take(5)->get(['id', 'title', 'publish_time', 'image', 'permalink', 'lang']);

        $pages = Page::search($search)->orderBy('created_at', 'desc')->take(5)->get(['id', 'title', 'permalink', 'lang']);

        $documents = Document::search($search)->orderBy('document_date', 'desc')->take(5)->get(['id', 'name', 'title', 'document_date', 'anonymous_url', 'language', 'content_type', 'created_at', 'summary']);

        return back()->with('search', ['calendar' => $calendar, 'news' => $news, 'pages' => $pages, 'documents' => $documents]);
    }

    public function sendFeedback(Request $request)
    {
        $data = $request->all();

        Mail::to('it@vusa.lt')->queue(new \App\Mail\FeedbackMail($data['feedback'], auth()->user(), $data['href'], $data['selectedText']));

        return back()->with('success', 'Ačiū už atsiliepimą!');

    }
}
