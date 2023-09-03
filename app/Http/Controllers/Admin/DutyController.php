<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetAttachableTypesForDuty;
use App\Http\Controllers\LaravelResourceController;
use App\Models\Duty;
use App\Models\Role;
use App\Models\Type;
use App\Models\User;
use App\Services\ModelIndexer;
use App\Services\ResourceServices\DutyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DutyController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [Duty::class, $this->authorizer]);

        $indexer = new ModelIndexer(new Duty(), $request, $this->authorizer);

        $duties = $indexer
            ->setEloquentQuery([fn (Builder $query) => $query->with('institution')])
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return Inertia::render('Admin/People/IndexDuty', [
            'duties' => $duties,
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

        return Inertia::render('Admin/People/CreateDuty', [
            'dutyTypes' => Type::where('model_type', Duty::class)->get(),
            'roles' => Role::all(),
            'assignableInstitutions' => DutyService::getInstitutionsForUpserts($this->authorizer),
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
        $duty->users()->syncWithPivotValues($request->users, ['start_date' => now()->subDay()]);

        return redirect()->route('duties.index')->with('success', trans_choice('messages.created', 0, ['model' => trans_choice('entities.duty.model', 1)]));
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

        $duty->load('institution', 'types', 'roles', 'current_users');

        $attachable_duty_types = GetAttachableTypesForDuty::execute();

        return Inertia::render('Admin/People/EditDuty', [
            'duty' => $duty,
            'roles' => Role::all(),
            'dutyTypes' => $attachable_duty_types->values(),
            'assignableInstitutions' => DutyService::getInstitutionsForUpserts($this->authorizer),
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
            // array of integers
            'types' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $duty) {
            $duty->update($request->only('name', 'description', 'email', 'places_to_occupy', 'extra_attributes'));

            $duty->users()->syncWithPivotValues($request->users, ['start_date' => now()->subDay()]);

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

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => trans_choice('entities.duty.model', 1)]));
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

        return redirect()->route('duties.index')->with('info', trans_choice('messages.deleted', 0, ['model' => trans_choice('entities.duty.model', 1)]));
    }

    public function setAsStudentRepresentatives(Request $request)
    {
        $this->authorize('update', [Duty::class, $this->authorizer]);

        $request->validate([
            'duties' => 'required|array',
        ]);

        $duties = Duty::find($request->duties);

        // attach student representative type and role to duties, but without duplication errors
        foreach ($duties as $duty) {
            $duty->types()->syncWithoutDetaching(Type::where('title', 'Studentų atstovas')->first());
            $duty->roles()->syncWithoutDetaching(Role::where('name', 'Studentų atstovas')->first());
        }

        return back()->with('success', 'Pareigybės sėkmingai atnaujintos!');
    }
}
