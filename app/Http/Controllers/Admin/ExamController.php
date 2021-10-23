<?php
namespace App\Http\Controllers\Admin;

use App\Models\Saziningai;
use App\Models\Saziningai_people;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class ExamController extends AdminBaseController {
    
    public function index(Request $request)
    {
        $i = 0;

        $searchText = '';
        if (isset(explode('searchText=', $request->fullUrl())[1])) {
            $searchText = explode('&', explode('searchText=', $request->fullUrl())[1])[0];
        }

        if (isset($searchText)) {
            $atsiskaitymai = Saziningai::orderBy('time','desc')->where('subject_name', 'like', '%' . $request->searchText . '%')->simplePaginate(20);

            foreach ($atsiskaitymai as $atsiskaitymas) {
                $atsiskaitymai[$i]->students_registered = Saziningai_people::where('exam_uuid', '=', $atsiskaitymas->uuid)->get()->count();
                $i++;
            }
        } else {
            $atsiskaitymai = Saziningai::orderBy('time','desc')->simplePaginate(20);
            foreach ($atsiskaitymai as $atsiskaitymas) {
                $atsiskaitymai[$i]->students_registered = Saziningai_people::where('exam_uuid', '=', $atsiskaitymas->uuid)->get()->count();
                $i++;
            }
        }

        return view('pages.admin.exam.index', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'atsiskaitymai' => $atsiskaitymai, 'searchText' => $searchText]);
    }

    public function edit($uuid, Request $request)
    {
        $atsiskaitymas = Saziningai::where('uuid', '=', $uuid)->first();

        return view('pages.admin.examEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'atsiskaitymas' => $atsiskaitymas]);
    }

    public function update($uuid, Request $request)
    {
        $rules = array(
            'name' => 'required',
            'contact' => 'required',
            'exam' => 'required',
            'padalinys' => 'required',
            'place' => 'required',
            'time' => 'required',
            'duration' => 'required',
            'subject_name' => 'required',
            'count' => 'required',
            'students_need' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
            return Redirect::to('/admin/saziningai/' . $uuid . '/redaguoti')->withInput()->withErrors(($validator));
        else {
            Saziningai::where('uuid', '=', $uuid)->update([
                'name' => $request->name,
                'contact' => $request->contact,
                'exam' => $request->exam,
                'padalinys' => $request->padalinys,
                'place' => $request->place,
                'time' => $request->time,
                'duration' => $request->duration,
                'subject_name' => $request->subject_name,
                'count' => $request->count,
                'students_need' => $request->students_need
            ]);
        }
        return redirect('/admin/saziningai?page=' . $request->page)->with('message', 'Atsiskaitymas sėkmingai atnaujintas.');
    }

    public function destroy(Request $request)
    {
        $uuid = $request->input('uuid');
        Saziningai::where('uuid', '=', $uuid)->delete();

        return response()->json('DELETED', 200);
    }
}