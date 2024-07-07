<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetPadaliniaiForUpserts;
use App\Http\Controllers\LaravelResourceController;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Type;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $indexer = new ModelIndexer(new Institution(), request(), $this->authorizer);

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
            'padaliniai' => GetPadaliniaiForUpserts::execute('institutions.create.all', $this->authorizer),
            'institutionTypes' => Type::where('model_type', Institution::class)->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Institution::class, $this->authorizer]);

        $request->validate([
            'name' => 'required',
            'alias' => 'nullable|unique:institutions,alias',
            'padalinys_id' => 'required',
        ]);

        // if request alias is null, create slug from name
        if (! $request->alias) {
            $request->merge(['alias' => Str::slug($request->name)]);
        }

        $institution = Institution::create($request->only('name', 'short_name', 'alias', 'padalinys_id', 'image_url', 'extra_attributes'));

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
        $institution->load('padalinys', 'users', 'matters')->load(['meetings' => function ($query) {
            $query->with('tasks', 'comments', 'files')->orderBy('start_time', 'asc');
        }])->load('activities.causer');

        // Inertia::share('layout.navBackground', $institution->image_url ?? null);

        return Inertia::render('Admin/People/ShowInstitution', [
            'institution' => [
                ...$institution->toArray(),
                'users' => $institution->users->unique('id')->values(),
                'managers' => $institution->managers(),
                'relatedInstitutions' => $institution->related_institution_relationshipables(),
                'sharepointPath' => $institution->padalinys ? $institution->sharepoint_path() : null,
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
                ...$institution->toArray(),
                'types' => $institution->types->pluck('id'),
            ],
            'institutionTypes' => Type::where('model_type', Institution::class)->get(),
            'padaliniai' => GetPadaliniaiForUpserts::execute('institutions.update.all', $this->authorizer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Institution $institution)
    {
        $this->authorize('update', [Institution::class, $institution, $this->authorizer]);

        // validate
        $request->validate([
            'name' => 'required',
            'padalinys_id' => 'required',
        ]);

        // TODO: short_name and shortname are used as columns in some tables. Need to make the same name.
        $institution->fill($request->only('name', 'short_name', 'description', 'image_url', 'extra_attributes'))->save();

        // check if super admin, then update padalinys_id
        if (auth()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $institution->update($request->only('padalinys_id'));
        }

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

        return back()->with('info', 'Institucija sėkmingai ištrinta!');
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
