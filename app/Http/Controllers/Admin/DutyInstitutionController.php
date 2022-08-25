<?php

namespace App\Http\Controllers\Admin;

use App\Models\DutyInstitution;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Inertia\Inertia;
use App\Models\Padalinys;

class DutyInstitutionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DutyInstitution::class, 'dutyInstitution');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $search = request()->input('search');

        $dutyInstitutions = DutyInstitution::with('padalinys:id,shortname')->when(!request()->user()->isAdmin(), function ($query) {
            $query->where('padalinys_id', '=', request()->user()->padalinys()->id);
        })->when(!is_null($search), function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")->orWhere('short_name', 'like', "%{$search}%")->orWhere('alias', 'like', "%{$search}%");
        })->paginate(20);


        return Inertia::render('Admin/Contacts/IndexDutyInstitutions', [
            'dutyInstitutions' => $dutyInstitutions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Admin/Contacts/CreateDutyInstitution', [
            'padaliniai' => Padalinys::orderBy('shortname_vu')->get()->map(function ($padalinys) {
                return [
                    'id' => $padalinys->id,
                    'shortname' => $padalinys->shortname,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validate
        $request->validate([
            'name' => 'required',
            'short_name' => 'required',
            'alias' => 'required|unique:duties_institutions,alias',
            'padalinys_id' => 'required',
        ]);

        // create
        $dutyInstitution = new DutyInstitution();
        $dutyInstitution->name = $request->name;
        $dutyInstitution->short_name = $request->short_name;
        $dutyInstitution->alias = $request->alias;
        $dutyInstitution->padalinys_id = $request->padalinys_id;
        $dutyInstitution->image_url = $request->image_url;
        $dutyInstitution->save();

        return redirect()->route('dutyInstitutions.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function show(DutyInstitution $dutyInstitution)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function edit(DutyInstitution $dutyInstitution)
    {
        // dd($dutyInstitution);

        return Inertia::render('Admin/Contacts/EditDutyInstitution', [
            'dutyInstitution' => $dutyInstitution,
            'duties' => $dutyInstitution->duties,
            'padaliniai' => Padalinys::orderBy('shortname_vu')->get()->map(function ($padalinys) {
                return [
                    'id' => $padalinys->id,
                    'shortname' => $padalinys->shortname,
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DutyInstitution $dutyInstitution)
    {
        // validate
        $request->validate([
            'name' => 'required',
            'short_name' => 'required',
            'padalinys_id' => 'required',
        ]);
        
        // TODO: short_name and shortname are used as columns in some tables. Need to make the same name.
        $dutyInstitution->update($request->only('name', 'short_name', 'description', 'padalinys_id', 'image_url', 'attributes'));

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function destroy(DutyInstitution $dutyInstitution)
    {
        //
    }

    public function searchForInstitutions(Request $request)
    {
        $data = $request->collect()['data'];

        $institutions = DutyInstitution::when(!$request->user()->isAdmin(), function ($query) use ($request) {
            $query->where('padalinys_id', '=', $request->user()->padalinys()->id);
            // check request for padaliniai, if not empty return only pages from request padaliniai
        })->where(function ($query) use ($data) {
            $query->where('name', 'like', "%{$data['name']}%")->orWhere('short_name', 'like', "%{$data['name']}%")->orWhere('alias', 'like', "%{$data['name']}%");
        })->get();

        $institutions = $institutions->map(function ($institution) {
            return [
                'id' => $institution->id,
                'name' => $institution->name,
                'alias' => $institution->alias,
            ];
        });

        return back()->with('search_other', $institutions);
    }
}
