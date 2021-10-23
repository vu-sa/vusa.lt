<?php
namespace App\Http\Controllers\Admin;

use App\Models\Saziningai_people;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class ExamPeopleController extends AdminBaseController {
    
    public function index(Request $request)
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
        return view('pages.admin.examPeople.index', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'zmones' => $zmones, 'searchText' => $searchText]);
    }

    public function edit($id, Request $request)
    {
        $zmogus = DB::table('saziningai_people')->leftJoin('saziningai', 'saziningai_people.exam_uuid', '=', 'saziningai.uuid')->where('id_p', '=', $id)->first();
        return view('pages.admin.examPeople.edit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'zmogus' => $zmogus]);
    }

    public function update($id, Request $request)
    {
        $rules = array('name' => 'students_need');
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
            return Redirect::to('/admin/examPeople/' . $id . '/edit')->withInput()->withErrors(($validator));
        else {
            Saziningai_people::where('id_p', '=', $id)->update([
                'name_p' => $request->name_p,
                'padalinys_p' => $request->padalinys_p,
                'contact_p' => $request->contact_p,
                'status_p' => $request->status_p
            ]);
        }

        return redirect('/admin/examPeople/?page=' . $request->page)->with('message', 'Užsiregistravęs stebėtojas sėkmingai atnaujintas.');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        Saziningai_people::where('id_p', '=', $id)->delete();

        return response()->json('DELETED', 200);
    }
}