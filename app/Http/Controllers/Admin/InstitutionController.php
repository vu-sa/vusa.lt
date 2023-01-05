<?php

namespace App\Http\Controllers\Admin;

use App\Models\Institution;
use App\Models\Duty;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Inertia\Inertia;
use App\Models\Padalinys;
use App\Models\Type;
use App\Models\Doing;
use App\Models\Relationshipable;
use App\Services\SharepointAppGraph;

class InstitutionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Institution::class, 'institution');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $search = request()->input('search');

        $institutions = Institution::with('padalinys:id,shortname')->when(!request()->user()->hasRole('Super Admin'), function ($query) {
            $query->where('padalinys_id', '=', request()->user()->padalinys()->id);
        })->when(!is_null($search), function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")->orWhere('short_name', 'like', "%{$search}%")->orWhere('alias', 'like', "%{$search}%");
        })->paginate(20);

        return Inertia::render('Admin/People/IndexInstitution', [
            'institutions' => $institutions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {       
        return Inertia::render('Admin/People/CreateInstitution', [
            'padaliniai' => Padalinys::orderBy('shortname_vu')->get()->map(function ($padalinys) {
                return [
                    'id' => $padalinys->id,
                    'shortname' => $padalinys->shortname,
                ];
            }),
            'institutionTypes' => Type::where('model_type', Institution::class)->get(),
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
        $request->validate([
            'name' => 'required',
            'short_name' => 'required',
            'alias' => 'required|unique:duties_institutions,alias',
            'padalinys_id' => 'required',
        ]);

        $institution = Institution::create($request->only('name', 'short_name', 'alias', 'padalinys_id', 'image_url'));

        $institution->extra_attributes = $request->extra_attributes;
        $institution->save();

        $institution->types()->sync($request->types);

        return redirect()->route('institutions.index')->with('success', 'Institucija sėkmingai sukurta!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Http\Response
     */
    public function show(Institution $institution)
    {
        $institution->load('types', 'padalinys', 'users', 'matters.meetings.documents');

        $users = $institution->users()->get()->unique('id')->values();

        $sharepointFiles = [];        
        
        if ($institution->documents->count() > 0) {
            $graph = new SharepointAppGraph();
        
            $sharepointFiles = $graph->collectSharepointFiles($institution->documents);
        }

        $receivedRelationships = $institution->receivedRelationshipModels();
        $givenRelationships = $institution->givenRelationshipModels();

        return Inertia::render('Admin/People/ShowInstitution', [
            'institution' => [
                ...$institution->toArray(), 
                'users' => $users,
                'types' => $institution->types->load('documents')->map(function ($type) use ($institution) {
                    return 
                        [...$type->toArray(), 
                        'givenRelationships' => Relationshipable::where('relationshipable_type', Type::class)
                            ->where('relationshipable_id', $type->id)->get()
                            ->map(function ($relationshipable) use ($institution)
                            {
                                $relationships = $relationshipable->getRelatedModelsFromGivenType(Institution::class, $institution->id, true);
                                return [
                                    'relationshipable' => $relationshipable,
                                    'relationships' => $relationships,
                                ];
                            }),
                        'receivedRelationships' => Relationshipable::where('relationshipable_type', Type::class)->where('related_model_id', $type->id)->get()->map(function ($relationshipable) use ($institution) {
                            $relationships = $relationshipable->getRelatedModelsFromReceiverType(Institution::class, $institution->id, true);
                            return [
                                'relationshipable' => $relationshipable,
                                'relationships' => $relationships,
                            ];
                        }),
                        ];
                }),
                'sharepointFiles' => $sharepointFiles,
                'receivedRelationships' => $receivedRelationships,
                'givenRelationships' => $givenRelationships,
                'lastMeeting' => $institution->lastMeeting(),
            ],
            'doingTypes' => Type::where('model_type', Doing::class)->get()->map(function ($doingType) {
                return [
                    'value' => $doingType->id,
                    'label' => $doingType->title,
                ];
            }),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Http\Response
     */
    public function edit(Institution $institution)
    {
        return Inertia::render('Admin/People/EditInstitution', [
            'institution' => [
                ...$institution->toArray(),
                'types' => $institution->types->first()?->id,
            ],
            'duties' => $institution->duties->sortBy('order')->values(),
            'institutionTypes' => Type::where('model_type', Institution::class)->get(),
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
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Institution $institution)
    {
        // validate
        $request->validate([
            'name' => 'required',
            'short_name' => 'required',
            'padalinys_id' => 'required',
        ]);
        
        // TODO: short_name and shortname are used as columns in some tables. Need to make the same name.
        $institution->update($request->only('name', 'short_name', 'description', 'padalinys_id', 'image_url', 'extra_attributes'));

        $institution->types()->sync($request->types);

        return back()->with('success', 'Institucija sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Http\Response
     */
    public function destroy(Institution $institution)
    {
        return back()->with('info', 'Institucijų šiuo metu negalima ištrinti...');
    }

    public function reorderDuties(Request $request)
    {
        foreach ($request->duties as $duty) {
            $dutyModel = Duty::find($duty['id']);
            $dutyModel->order = $duty['order'];
            $dutyModel->save();
        }

        return back()->with('success', 'Pareigų tvarka sėkmingai atnaujinta!');
    }
}
