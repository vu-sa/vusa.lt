<?php

namespace App\Http\Controllers\Admin;

use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class AgendaController extends AdminBaseController {

    public function index(Request $request)
    {
        $agenda = Agenda::orderBy('date', 'desc')->simplePaginate(15);

        return view('pages.admin.agenda.index', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'agenda' => $agenda]);
    }

    public function create(Request $request)
    {
        setlocale(LC_ALL, 'lt_LT.UTF-8');

        return view('pages.admin.agenda.create', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null]);
    }

    public function store(Request $request)
    {
        $rules = array(
            'title' => 'required',
            'date' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/agenda/create')->withInput()->withErrors(($validator));
        } else {
            $agenda = new Agenda();
            $agenda->title = $request->title;
            $agenda->description = $request->description ?? '';
            $agenda->date = $request->date;
            $agenda->editor = $request->User()['id'];
            $agenda->owner = $agenda->editor;
            $agenda->save();
        }

        return redirect('/admin/agenda')->with('message', 'Darbotvarkės įrašas pridėtas.');
    }

    public function edit($id, Request $request)
    {
        $agendaEvent = Agenda::where('id', '=', $id)->first();
        setlocale(LC_ALL, 'lt_LT.UTF-8');

        return view('pages.admin.agenda.edit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'agendaEvent' => $agendaEvent, 'name' => null]);
    }

    public function update($id, Request $request)
    {
        $rules = array(
            'title' => 'required',
            'date' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Redirect::to('/admin/agenda/' . $id . '/edit')->withInput()->withErrors(($validator));
        } else {
            Agenda::where('id', '=', $id)->update([
                'title' => $request->title,
                'description' => $request->description ?? '',
                'date' => $request->date,
                'editor' => $request->User()->id
            ]);
        }
        return redirect('/admin/agenda')->with('message', 'Darbotvarkės įrašas atnaujintas.');
    }

    public function destroy(Request $request)
    {
        $itemId = $request->input('id');

        if (Agenda::where('id', '=', $itemId)->delete() == 1) {
            return response()->json('DELETED', 200);
        } else {
            return response()->json('NOT DELETED', 200);
        }
    }
}