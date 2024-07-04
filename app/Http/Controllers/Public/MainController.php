<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Mail\ConfirmExamRegistration;
use App\Mail\ConfirmMemberRegistration;
use App\Mail\ConfirmObserverRegistration;
use App\Mail\InformSaziningaiAboutObserverRegistration;
use App\Mail\InformSaziningaiAboutRegistration;
use App\Models\Calendar;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Page;
use App\Models\Registration;
use App\Models\RegistrationForm;
use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExamObserver;
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

    public function storeSaziningaiExamRegistration()
    {
        $request = request();

        $saziningaiExam = SaziningaiExam::create([
            'uuid' => bin2hex(random_bytes(15)),
            'subject_name' => $request->subject_name,
            'name' => $request->name,
            'padalinys_id' => $request->padalinys_id,
            'place' => $request->place,
            'email' => $request->email,
            'duration' => $request->duration,
            'exam_holders' => $request->exam_holders,
            'exam_type' => $request->exam_type,
            'phone' => $request->phone,
            'students_need' => $request->students_need,
        ]);

        // Store new flow
        foreach ($request->flows as $flow) {
            $saziningaiExamFlow = new SaziningaiExamFlow();
            $saziningaiExamFlow->exam_uuid = $saziningaiExam->uuid;
            $saziningaiExamFlow->start_time = date('Y-m-d H:i:s', strtotime($flow['start_time']));
            $saziningaiExamFlow->save();
        }

        $firstFlow = $saziningaiExam->flows->first();

        Mail::to('saziningai@vusa.lt')->send(new InformSaziningaiAboutRegistration($saziningaiExam, $firstFlow));
        Mail::to($saziningaiExam->email)->send(new ConfirmExamRegistration($saziningaiExam, $firstFlow));

        return redirect()->route('saziningaiExams.registered');
    }

    public function storeSaziningaiExamObserver()
    {
        $request = request();

        $saziningaiExamFlow = SaziningaiExamFlow::find($request->flow);

        $saziningaiExamObserver = SaziningaiExamObserver::create([
            'exam_uuid' => $saziningaiExamFlow->exam_uuid,
            'flow' => $request->flow,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'padalinys_id' => $request->padalinys_id,
            'has_arrived' => 'neatvyko',
        ]);

        Mail::to('saziningai@vusa.lt')->send(new InformSaziningaiAboutObserverRegistration($saziningaiExamObserver, $saziningaiExamFlow));
        Mail::to($saziningaiExamObserver->email)->send(new ConfirmObserverRegistration($saziningaiExamFlow));
    }

    public function storeMemberRegistration()
    {
        // store registration
        // 1 registration is to MIF camp, 2 is for VU SA and PKP members

        $data = request()->all();
        if ($data['registrationForm'] == 3) {
            $this->storeRegistration(RegistrationForm::find(3));

            return;
        } else {
            $this->storeRegistration(RegistrationForm::find(2));
            $registerLocation = new Padalinys();
            $chairPerson = new User();

            // if whereToRegister is int, then it is a padalinys id
            if (is_int($data['whereToRegister'])) {
                $registerPadalinys = Padalinys::find($data['whereToRegister']);
                $registerLocation = __($registerPadalinys->fullname);
                $chairDuty = $registerPadalinys->duties()->whereHas('types', function ($query) {
                    $query->where('slug', 'pirmininkas');
                })->first();
                $chairPerson = $chairDuty->users->first();
                $chairEmail = $chairDuty->email;
            } else {
                switch ($data['whereToRegister']) {
                    case 'hema':
                        $registerLocation = 'HEMA ('.__('Istorinių Europos kovos menų klubas').')';
                        $chairEmail = 'hema@vusa.lt';
                        break;

                    case 'jek':
                        $registerLocation = 'VU '.__('Jaunųjų energetikų klubas');
                        $chairEmail = 'vujek@jek.lt';
                        break;

                    default:
                        abort(500);
                        break;
                }
            }

            // send mail to the registered person
            Mail::to($data['email'])->send(new ConfirmMemberRegistration($data, $registerLocation, $chairPerson, $chairEmail));
            Notification::send($chairPerson, new MemberRegistered($data, $registerLocation, $chairEmail));
        }
    }

    public function storeRegistration(RegistrationForm $registrationForm)
    {
        $registration = new Registration;
        $registration->data = request()->except('registrationForm', 'padalinys');
        $registration->registration_form_id = $registrationForm->id;
        $registration->save();
    }

    public function getMainNews()
    {
        // get last 4 news by publishing date
        $padalinys = Padalinys::where('shortname', '=', 'VU SA')->first();
        $mainNews = News::select('title', 'short', 'image')->where([['padalinys_id', '=', $padalinys->id], ['draft', '=', 0]])->orderBy('publish_time', 'desc')->take(4)->get();

        return response()->json($mainNews, 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);
        //
    }

    public function search()
    {
        // get search query
        $search = request()->data['input'];

        // search calendar events by title and get 5 most recent with only title, date and id, but spaces are not important
        $calendar = Calendar::search($search)->orderBy('date', 'desc')->take(5)->get()->map(function ($calendar) {
            return [
                'id' => $calendar->id,
                'title' => $calendar->title,
                'date' => $calendar->date,
                'permalink' => $calendar->permalink,
                'lang' => $calendar->lang,
            ];
        });

        // search news by title and get 5 most recent with only title, publish_time and id and permalink
        $news = News::search($search)->orderBy('publish_time', 'desc')->take(5)->get()->map(function ($news) {
            return [
                'id' => $news->id,
                'title' => $news->title,
                'publish_time' => $news->publish_time,
                'permalink' => $news->permalink,
                'lang' => $news->lang,
            ];
        });

        // search pages by title and get 5 most recent with only title, id and permalink
        $pages = Page::search($search)->orderBy('created_at', 'desc')->take(5)->get()->map(function ($page) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'permalink' => $page->permalink,
                'lang' => $page->lang,
            ];
        });

        return back()->with('search_calendar', $calendar)->with('search_news', $news)->with('search_pages', $pages);
    }

    public function sendFeedback(Request $request)
    {
        $data = $request->all();

        Mail::to('it@vusa.lt')->queue(new \App\Mail\FeedbackMail($data['feedback'], auth()->user(), $data['href'], $data['selectedText']));

        return back()->with('success', 'Ačiū už atsiliepimą!');

    }
}
