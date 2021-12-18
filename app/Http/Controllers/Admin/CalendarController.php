<?php

namespace App\Http\Controllers\Admin;

use App\Models\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class CalendarController extends AdminBaseController {

    /**
     * Kalendoriaus ir darbotvarkės valdymas
     */
    public function index(Request $request)
    {
        $events = Calendar::orderBy('date', 'desc')->simplePaginate(15);

        return view('pages.admin.calendar.index', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'events' => $events]);
    }

    public function create(Request $request)
    {
        setlocale(LC_ALL, 'lt_LT.UTF-8');

        return view('pages.admin.calendar.create', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null]);
    }

    public function store(Request $request)
    {
        $rules = array(
            'title' => 'required',
            'category' => 'required',
            'date' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/calendar/create')->withInput()->withErrors(($validator));
        } else {
            $color = '';
            if ($request->category == 'akadem') {
                $color = 'red';
            } elseif ($request->category == 'soc') {
                $color = 'yellow';
            } elseif ($request->category == 'sventes') {
                $color = 'grey';
            } else {
                $color = '';
            }

            $calendar = new Calendar();
            $calendar->title = $request->title;
            $calendar->descr = $request->descr;
            $calendar->date = $request->date;
            $calendar->classname = $color;
            $calendar->editor = $request->User()->id;
            $calendar->save();
        }

        return redirect('/admin/calendar')->with('message', 'Kalendoriaus įrašas pridėtas.');
    }

    public function edit($id, Request $request)
    {
        $calendarEvent = Calendar::where('id', '=', $id)->first();
        setlocale(LC_ALL, 'lt_LT.UTF-8');

        $calendarEvent->date = date('Y-m-d', strtotime($calendarEvent->date));

        $category = '';
        if ($calendarEvent['classname'] == 'red') {
            $category = 'akadem';
        } elseif ($calendarEvent['classname'] == 'yellow') {
            $category = 'soc';
        } elseif ($calendarEvent['classname'] == 'grey') {
            $category = 'sventes';
        } else {
            $category = '0';
        }

        return view('pages.admin.calendar.edit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'calendarEvent' => $calendarEvent, 'category' => $category, 'name' => null]);
    }

    public function update($id, Request $request)
    {
        $rules = array(
            'title' => 'required',
            'category' => 'required',
            'date' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/calendar/' . $id . '/edit')->withInput()->withErrors(($validator));
        } else {
            $color = '';
            if ($request->category == 'akadem') {
                $color = 'red';
            } elseif ($request->category == 'soc') {
                $color = 'yellow';
            } elseif ($request->category == 'sventes') {
                $color = 'grey';
            } else {
                $color = '';
            }

            Calendar::where('id', '=', $id)->update([
                'title' => $request->title,
                'descr' => $request->descr,
                'date' => $request->date,
                'classname' => $color,
                'editor' => $request->User()->id
            ]);
        }

        return redirect('/admin/calendar')->with('message', 'Kalendoriaus įrašas atnaujintas.');
    }

    public function destroy(Request $request)
    {
        $itemId = $request->input('itemId');

        if (Calendar::where('id', '=', $itemId)->delete() == 1) {
            return response()->json('DELETED', 200);
        } else {
            return response()->json('NOT DELETED', 200);
        }
    }
}