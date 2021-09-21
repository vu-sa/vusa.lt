<?php
namespace App\Http\Controllers\Admin;

use App\Models\Saziningai;
use App\Models\Saziningai_people;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class ExamController extends AdminBaseController {
    
public function exams(Request $request)
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

        return view('pages.admin.exams', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'atsiskaitymai' => $atsiskaitymai, 'searchText' => $searchText]);
    }

    public function getEditExam($uuid, Request $request)
    {
        $atsiskaitymas = Saziningai::where('uuid', '=', $uuid)->first();

        return view('pages.admin.examEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'atsiskaitymas' => $atsiskaitymas]);
    }

    public function postEditExam($uuid, Request $request)
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

    public function deleteExam(Request $request)
    {
        $uuid = $request->input('uuid');
        Saziningai::where('uuid', '=', $uuid)->delete();

        return response()->json('DELETED', 200);
    }

    public function getRegisteredExamPeople(Request $request)
    {
        $searchText = '';
        if (isset(explode('searchText=', $request->fullUrl())[1])) {
            $searchText = explode('&', explode('searchText=', $request->fullUrl())[1])[0];
        }

        if (isset($searchText)) {
            $zmones = DB::table('saziningai_people')->leftJoin('saziningai', 'saziningai_people.exam_uuid', '=', 'saziningai.uuid')->where('subject_name', 'like', '%' . $request->searchText . '%')->simplePaginate(20);
        } else {
            $zmones = DB::table('saziningai_people')->leftJoin('saziningai', 'saziningai_people.exam_uuid', '=', 'saziningai.uuid')->simplePaginate(20);
        }

//        $zmones = DB::table('saziningai_people')->leftJoin('saziningai', 'saziningai_people.exam_uuid', '=', 'saziningai.uuid')->get();
        return view('pages.admin.examPeople', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'zmones' => $zmones, 'searchText' => $searchText]);
    }

    public function deleteRegisteredExamPeople(Request $request)
    {
        $id = $request->input('id');
        Saziningai_people::where('id_p', '=', $id)->delete();

        return response()->json('DELETED', 200);
    }

    public function getEditRegisteredExamPeople($id, Request $request)
    {
        $zmogus = DB::table('saziningai_people')->leftJoin('saziningai', 'saziningai_people.exam_uuid', '=', 'saziningai.uuid')->where('id_p', '=', $id)->first();
        return view('pages.admin.examPeopleEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'zmogus' => $zmogus]);
    }

    public function postEditRegisteredExamPeople($id, Request $request)
    {
        $rules = array('name' => 'students_need');
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
            return Redirect::to('/admin/saziningai-uzsiregistrave/' . $id . '/redaguoti')->withInput()->withErrors(($validator));
        else {
            Saziningai_people::where('id_p', '=', $id)->update([
                'name_p' => $request->name_p,
                'padalinys_p' => $request->padalinys_p,
                'contact_p' => $request->contact_p,
                'status_p' => $request->status_p
            ]);
        }

        return redirect('/admin/saziningai-uzsiregistrave/?page=' . $request->page)->with('message', 'Užsiregistravęs stebėtojas sėkmingai atnaujintas.');
    }
}