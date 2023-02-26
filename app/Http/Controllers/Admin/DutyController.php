<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DutyController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [Duty::class, $this->authorizer]);

        $search = request()->input('text');

        $duties = $this->indexer->execute(Duty::class, $search, 'name', $this->authorizer, true);

        return Inertia::render('Admin/People/IndexDuty', [
            'duties' => $duties->with('institution')->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Duty::class, $this->authorizer]);

        $institutions = $this->getInstitutionsForForm();

        return Inertia::render('Admin/People/CreateDuty', [
            'dutyTypes' => Type::where('model_type', Duty::class)->get(),
            'institutions' => $institutions,
            'roles' => Role::all(),
            'assignableInstitutions' => $this->getInstitutionsForForm(),
            'assignableUsers' => User::select('id', 'name', 'profile_photo_path')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Duty::class, $this->authorizer]);

        $request->validate([
            'name' => 'required',
            'institution_id' => 'required',
            'places_to_occupy' => 'required|numeric',
        ]);

        $duty = Duty::create([
            'name' => $request->name,
            'places_to_occupy' => $request->places_to_occupy,
            'email' => $request->email,
            'description' => $request->description,
            'institution_id' => $request->institution_id,
        ]);

        $duty->extra_attributes = $request->extra_attributes;
        $duty->save();

        $duty->types()->sync($request->type);
        $duty->users()->syncWithPivotValues($request->users, ['start_date' => now()]);

        return redirect()->route('duties.index')->with('success', 'Pareigybė sėkmingai sukurta!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Duty $duty)
    {
        $this->authorize('view', [Duty::class, $duty, $this->authorizer]);

        return Inertia::render('Admin/People/ShowDuty', [
            'duty' => $duty->load('institution', 'users', 'activities.causer'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Duty $duty)
    {
        $this->authorize('update', [Duty::class, $duty, $this->authorizer]);

        $duty->load('institution', 'types', 'roles', 'users');

        return Inertia::render('Admin/People/EditDuty', [
            'duty' => $duty,
            'roles' => Role::all(),
            'dutyTypes' => Type::where('model_type', Duty::class)->get(),
            'assignableInstitutions' => $this->getInstitutionsForForm(),
            // TODO: shouldn't return all users?
            'assignableUsers' => User::select('id', 'name', 'profile_photo_path')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Duty $duty)
    {
        $this->authorize('update', [Duty::class, $duty, $this->authorizer]);

        $request->validate([
            'name' => 'required',
            'users' => 'nullable|array',
            'institution_id' => 'required',
            'places_to_occupy' => 'required|numeric',
        ]);

        DB::transaction(function () use ($request, $duty) {
            $duty->update($request->only('name', 'description', 'email', 'places_to_occupy', 'extra_attributes'));

            $duty->users()->syncWithPivotValues($request->users, ['start_date' => now()]);

            $duty->institution()->disassociate();
            $duty->institution()->associate($request->institution_id);
            $duty->save();

            if (true) {
                // check if user is super admin
                if ($request->has('roles')) {
                    $roles = Role::find($request->roles);

                    // foreach check if super admin
                    foreach ($roles as $role) {
                        if ($role->name == config('permission.super_admin_role_name')) {
                            abort(403, 'Negalima priskirti šios rolės pareigybėms! Bandykite iš naujo');
                        }
                    }

                    $duty->syncRoles($roles);
                } else {
                    $duty->syncRoles([]);
                }
            }

            $duty->types()->sync($request->types);
        });

        return back()->with('success', 'Pareigybė sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Duty $duty)
    {
        $this->authorize('delete', [Duty::class, $duty, $this->authorizer]);

        $duty->delete();

        return redirect()->route('duties.index')->with('info', 'Pareigybė sėkmingai ištrinta!');
    }

    private function getInstitutionsForForm(): Collection
    {
        return Institution::select('id', 'name', 'alias', 'padalinys_id')->when(! request()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) {
            $query->whereIn('padalinys_id', auth()->user()->padaliniai->pluck('id'));
        })->with('padalinys:id,shortname')->get();
    }
}
