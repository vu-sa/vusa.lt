<?php

namespace App\Http\Controllers\Admin;

use App\Models\Agenda;
use App\Models\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class AgendaController extends AdminBaseController {

    /**
     * Kalendoriaus ir darbotvarkės valdymas
     */
    public function calendar(Request $request)
    {
        $events = Calendar::orderBy('date', 'desc')->simplePaginate(15);
        $events2 = Agenda::orderBy('date', 'desc')->simplePaginate(15);

        return view('pages.admin.calendar', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'events' => $events, 'events2' => $events2]);
    }

    public function getAddCalendar(Request $request)
    {
        setlocale(LC_ALL, 'lt_LT.UTF-8');

        return view('pages.admin.calendarAdd', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null]);
    }

    public function postAddCalendar(Request $request)
    {
        $rules = array(
            'title' => 'required',
            'category' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/kalendorius/prideti')->withInput()->withErrors(($validator));
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

        return redirect('/admin/kalendorius')->with('message', 'Kalendoriaus įrašas pridėtas.');
    }

    public function getEditCalendar($id, Request $request)
    {
        $event = Calendar::where('id', '=', $id)->first();
        setlocale(LC_ALL, 'lt_LT.UTF-8');

        $category = '';
        if ($event['classname'] == 'red') {
            $category = 'akadem';
        } elseif ($event['classname'] == 'yellow') {
            $category = 'soc';
        } elseif ($event['classname'] == 'grey') {
            $category = 'sventes';
        } else {
            $category = '0';
        }

        return view('pages.admin.calendarEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'event' => $event, 'category' => $category, 'name' => null]);
    }

    public function postEditCalendar($id, Request $request)
    {
        $rules = array(
            'title' => 'required',
            'category' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/kalendorius/' . $id . '/redaguoti')->withInput()->withErrors(($validator));
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

        return redirect('/admin/kalendorius')->with('message', 'Kalendoriaus įrašas atnaujintas.');
    }

    public function deleteCalendar(Request $request)
    {
        $itemId = $request->input('itemId');

        if (Calendar::where('id', '=', $itemId)->delete() == 1) {
            return response()->json('DELETED', 200);
        } else {
            return response()->json('NOT DELETED', 200);
        }
    }

    public function agenda(Request $request)
    {
        $agenda = Agenda::orderBy('date', 'desc')->simplePaginate(15);

        return view('pages.admin.agenda', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'agenda' => $agenda]);
    }

    public function getAddAgenda(Request $request)
    {
        setlocale(LC_ALL, 'lt_LT.UTF-8');

        return view('pages.admin.agendaAdd', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null]);
    }

    public function postAddAgenda(Request $request)
    {
        $rules = array(
            'title' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/darbotvarke/prideti')->withInput()->withErrors(($validator));
        } else {
            $agenda = new Agenda();
            $agenda->title = $request->title;
            $agenda->description = $request->description;
            $agenda->date = $request->date;
            $agenda->editor = $request->User()['id'];
            $agenda->save();
        }

        return redirect('/admin/darbotvarke')->with('message', 'Darbotvarkės įrašas pridėtas.');
    }

    public function getEditAgenda($id, Request $request)
    {
        $agenda = Agenda::where('id', '=', $id)->first();
        setlocale(LC_ALL, 'lt_LT.UTF-8');

        return view('pages.admin.agendaEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'agenda' => $agenda, 'name' => null]);
    }

    public function postEditAgenda($id, Request $request)
    {
        $rules = array(
            'title' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/darbotvarke/' . $id . '/redaguoti')->withInput()->withErrors(($validator));
        } else {
            Agenda::where('id', '=', $id)->update([
                'title' => $request->title,
                'description' => $request->description,
                'date' => $request->date,
                'editor' => $request->User()->id
            ]);
        }
        return redirect('/admin/darbotvarke')->with('message', 'Kalendoriaus įrašas atnaujintas.');
    }

    public function deleteAgenda(Request $request)
    {
        $itemId = $request->input('itemId');

        if (Agenda::where('id', '=', $itemId)->delete() == 1) {
            return response()->json('DELETED', 200);
        } else {
            return response()->json('NOT DELETED', 200);
        }
    }
}