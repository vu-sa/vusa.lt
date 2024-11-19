<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetAttachableTypesForDuty;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDutyRequest;
use App\Models\Duty;
use App\Models\Role;
use App\Models\Type;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use App\Services\ResourceServices\DutyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DutyController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Duty::class);

        $indexer = new ModelIndexer(new Duty);

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
        $this->authorize('create', Duty::class);

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
    public function store(StoreDutyRequest $request)
    {
        $duty = new Duty;

        $duty->fill($request->safe()->except('types', 'roles', 'current_users'))->save();

        $duty->types()->sync($request->types);

        $this->handleUsersUpdate(new Collection($duty->current_users->pluck('id')), new Collection($request->current_users), $duty);

        return redirect()->route('duties.index')->with('success', trans_choice('messages.created', 0, ['model' => trans_choice('entities.duty.model', 1)]));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Duty $duty)
    {
        $this->authorize('view', $duty);

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
        $this->authorize('update', $duty);

        $duty->load('institution', 'types', 'roles', 'current_users');

        return Inertia::render('Admin/People/EditDuty', [
            'duty' => $duty->toFullArray(),
            'roles' => Role::all(),
            'dutyTypes' => GetAttachableTypesForDuty::execute()->values(),
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
        $this->authorize('update', $duty);

        $request->validate([
            'name' => 'required',
            'current_users' => 'nullable|array',
            'institution_id' => 'required',
            'places_to_occupy' => 'required|numeric',
            // array of integers
            'types' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $duty) {
            $duty->update($request->only('name', 'description', 'email', 'places_to_occupy'));

            $this->handleUsersUpdate(new Collection($duty->current_users->pluck('id')), new Collection($request->current_users), $duty);

            $duty->institution()->disassociate();
            $duty->institution()->associate($request->institution_id);
            $duty->save();

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

            $duty->types()->sync($request->types);
        });

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => trans_choice('entities.duty.model', 1)]));
    }

    private function handleUsersUpdate(Collection $existing_users, Collection $duty_users, Duty $duty)
    {
        $new = $duty_users->diff($existing_users);
        $removed = $existing_users->diff($duty_users);

        // remove users from duty
        foreach ($removed as $user) {
            $duty->users()->updateExistingPivot($user, ['end_date' => now()->subDay()]);
        }

        // add users to duty
        foreach ($new as $user) {
            $duty->users()->attach($user, ['start_date' => now()->subDay()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Duty $duty)
    {
        $this->authorize('delete', $duty);

        $duty->delete();

        return redirect()->route('duties.index')->with('info', trans_choice('messages.deleted', 0, ['model' => trans_choice('entities.duty.model', 1)]));
    }

    public function restore(Duty $duty)
    {
        $this->authorize('restore', $duty);

        $duty->restore();

        return back()->with('success', 'Pareigybė sėkmingai atkurta!');
    }
}
