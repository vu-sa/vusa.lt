<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Type;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Institution::class, $this->authorizer]);

        $indexer = new ModelIndexer(new Institution, request(), $this->authorizer);

        $institutions = $indexer
            ->setEloquentQuery([
                fn (Builder $query) => $query->with(['meetings' => fn ($query) => $query->orderBy('start_time'),
                ])])
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(15);

        // also check if empty array
        return Inertia::render('Admin/People/IndexInstitution', [
            'institutions' => $institutions,
            'types' => Type::where('model_type', Institution::class)->get(),
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
            'assignableTenants' => GetTenantsForUpserts::execute('institutions.create.all', $this->authorizer),
            'institutionTypes' => Type::where('model_type', Institution::class)->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInstitutionRequest $request)
    {
        $institution = new Institution;

        $institution->fill($request->safe()->except('types'))->save();

        $institution->types()->sync($request->types);

        return redirect()->route('institutions.index')->with('success', 'Institucija sėkmingai sukurta!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Institution $institution)
    {
        $this->authorize('view', [Institution::class, $institution, $this->authorizer]);

        // TODO: only show current_users
        $institution->load('tenant', 'duties.current_users', 'matters')->load(['meetings' => function ($query) {
            $query->with('tasks', 'comments', 'files')->orderBy('start_time', 'asc');
        }])->load('activities.causer');

        // Inertia::share('layout.navBackground', $institution->image_url ?? null);

        return Inertia::render('Admin/People/ShowInstitution', [
            'institution' => [
                ...$institution->toArray(),
                'current_users' => $institution->duties->load('current_users')->pluck('current_users')->flatten()->unique('id')->values(),
                'managers' => $institution->managers(),
                'relatedInstitutions' => $institution->related_institution_relationshipables(),
                'sharepointPath' => $institution->tenant ? $institution->sharepoint_path() : null,
                'lastMeeting' => $institution->lastMeeting(),
            ],
            'doingTypes' => Type::where('model_type', Doing::class)->get(['id', 'title']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Institution $institution)
    {
        $this->authorize('update', [Institution::class, $institution, $this->authorizer]);

        $institution->load('types')->load(['duties' => function ($query) {
            $query->with('current_users')->orderBy('order', 'asc');
        }]);

        return Inertia::render('Admin/People/EditInstitution', [
            'institution' => [
                ...$institution->toFullArray(),
                'types' => $institution->types->pluck('id'),
            ],
            'institutionTypes' => Type::where('model_type', Institution::class)->get(),
            'assignableTenants' => GetTenantsForUpserts::execute('institutions.update.all', $this->authorizer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInstitutionRequest $request, Institution $institution)
    {
        $institution->fill($request->safe()->except('tenant_id', 'types'));

        // check if super admin, then update tenant_id
        if (auth()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $institution->fill($request->safe()->only('tenant_id'));
        }

        $institution->save();

        // get only types id
        $institution->types()->sync($request->types);

        return back()->with('success', 'Institucija sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Institution $institution)
    {
        $this->authorize('delete', [Institution::class, $institution, $this->authorizer]);

        // check if auth user is from this institution
        if (auth()->user()->institutions->contains($institution)) {
            return back()->with('error', 'Negalima ištrinti institucijos, kurioje esate!');
        }

        $institution->delete();

        return redirect()->route('institutions.index')->with('info', 'Institucija sėkmingai ištrinta!');
    }

    /**
     * Restore the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(Institution $institution)
    {
        $this->authorize('restore', [Institution::class, $institution, $this->authorizer]);

        $institution->restore();

        return back()->with('success', 'Institucija sėkmingai atkurta!');
    }

    /**
     * reorderDuties
     * Duties are ordered in the frontend array by the user. The order is saved in the database
     *
     * @return \Illuminate\Http\RedirectResponse
     */
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
