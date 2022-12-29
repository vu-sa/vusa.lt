<?php

namespace App\Http\Controllers\Admin;

use App\Models\DutyInstitution;
use App\Models\Duty;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Inertia\Inertia;
use App\Models\Padalinys;
use App\Models\Type;
use App\Models\Doing;
use App\Models\Relationshipable;
use App\Services\SharepointAppGraph;

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

        $dutyInstitutions = DutyInstitution::with('padalinys:id,shortname')->when(!request()->user()->hasRole('Super Admin'), function ($query) {
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
            'dutyInstitutionTypes' => Type::where('model_type', DutyInstitution::class)->get(),
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

        $dutyInstitution = DutyInstitution::create($request->only('name', 'short_name', 'alias', 'padalinys_id', 'image_url'));

        $dutyInstitution->attributes = $request->all()['attributes'];
        $dutyInstitution->save();

        $dutyInstitution->types()->sync($request->types);

        return redirect()->route('dutyInstitutions.index')->with('success', 'Institucija sėkmingai sukurta!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function show(DutyInstitution $dutyInstitution)
    {
        $dutyInstitution->load('types', 'padalinys', 'questions');
        $questions = $dutyInstitution->questions;

        $users = $dutyInstitution->duties->pluck('users')->flatten()->unique('id')->values();

        $sharepointFiles = [];        
        
        if ($dutyInstitution->documents->count() > 0) {
            $graph = new SharepointAppGraph();
        
            $sharepointFiles = $graph->collectSharepointFiles($dutyInstitution->documents);
        }

        $receivedRelationships = $dutyInstitution->receivedRelationshipModels();
        $givenRelationships = $dutyInstitution->givenRelationshipModels();

        return Inertia::render('Admin/Contacts/ShowDutyInstitution', [
            'dutyInstitution' => [
                ...$dutyInstitution->toArray(), 
                'questions' => $questions->load('doings')->loadCount('doings')->map(function ($question) {
                    return [...$question->toArray(), 'doings' => $question->doings->map(function ($doing) {
                        return [...$doing->toArray(), 'needs_attention' => $doing->needs_attention];
                    })];
                }),
                'users' => $users,
                'types' => $dutyInstitution->types->load('documents')->map(function ($type) use ($dutyInstitution) {
                    return 
                        [...$type->toArray(), 
                        'givenRelationships' => Relationshipable::where('relationshipable_type', Type::class)
                            ->where('relationshipable_id', $type->id)->get()
                            ->map(function ($relationshipable) use ($dutyInstitution)
                            {
                                $relationships = $relationshipable->getRelatedModelsFromGivenType(DutyInstitution::class, $dutyInstitution->id, true);
                                return [
                                    'relationshipable' => $relationshipable,
                                    'relationships' => $relationships,
                                ];
                            }),
                        'receivedRelationships' => Relationshipable::where('relationshipable_type', Type::class)->where('related_model_id', $type->id)->get()->map(function ($relationshipable) use ($dutyInstitution) {
                            $relationships = $relationshipable->getRelatedModelsFromReceiverType(DutyInstitution::class, $dutyInstitution->id, true);
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
                'lastMeetingDoing' => $dutyInstitution->lastMeetingDoing(),
                'doings' => $dutyInstitution->doings()->withWhereHas('documents')->get()->map(function ($doing) {
                    return [...$doing->toArray(), 'needs_attention' => $doing->needs_attention];
                }),
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
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function edit(DutyInstitution $dutyInstitution)
    {
        return Inertia::render('Admin/Contacts/EditDutyInstitution', [
            'dutyInstitution' => [
                ...$dutyInstitution->toArray(),
                'types' => $dutyInstitution->types->first()?->id,
            ],
            'duties' => $dutyInstitution->duties->sortBy('order')->values(),
            'dutyInstitutionTypes' => Type::where('model_type', DutyInstitution::class)->get(),
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

        $dutyInstitution->types()->sync($request->types);

        return back()->with('success', 'Institucija sėkmingai atnaujinta!');
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
