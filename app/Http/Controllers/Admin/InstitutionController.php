<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetInstitutionManagers;
use App\Services\ModelIndexer;
use App\Models\Institution;
use App\Models\Duty;
use Illuminate\Http\Request;
use App\Http\Controllers\ResourceController;
use Inertia\Inertia;
use App\Models\Padalinys;
use App\Models\Type;
use App\Models\Doing;
use App\Models\Pivots\Relationshipable;
use App\Services\SharepointAppGraph;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class InstitutionController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Institution::class, $this->authorizer]);

        $search = request()->input('text');

        $institutions = new ModelIndexer();
        $institutions = $institutions->execute(Institution::class, $search, 'name', $this->authorizer, null);

        return Inertia::render('Admin/People/IndexInstitution', [
            'institutions' => $institutions->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {       
        $this->authorize('create', [Institution::class, $this->authorizer]);
        
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
        $this->authorize('create', [Institution::class, $this->authorizer]);
        
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
        $this->authorize('view', [Institution::class, $institution, $this->authorizer]);
        
        $institution->load('types', 'padalinys', 'users', 'matters.meetings.documents', 'activities.causer');

        $users = $institution->users->unique('id')->values();
        $meetings = new EloquentCollection($institution->matters->pluck('meetings')->flatten());

        // get duties where belongs to same padalinys as institution, and where has permissions
        $institutionManagers = GetInstitutionManagers::execute($institution);

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
                'institutionManagers' => $institutionManagers,
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
                'meetings' => $meetings->unique(),
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
        $this->authorize('update', [Institution::class, $institution, $this->authorizer]);
        
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
        $this->authorize('update', [Institution::class, $institution, $this->authorizer]);
        
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
        $this->authorize('delete', [Institution::class, $institution, $this->authorizer]);
        
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
