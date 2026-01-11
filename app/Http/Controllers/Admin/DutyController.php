<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetAttachableTypesForDuty;
use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreDutyRequest;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\StudyProgram;
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

class DutyController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', Duty::class);

        $indexer = new ModelIndexer(new Duty);

        $duties = $indexer
            ->setEloquentQuery([fn (Builder $query) => $query->with('institution')])
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return $this->inertiaResponse('Admin/People/IndexDuty', [
            'duties' => $duties,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Duty::class);

        return $this->inertiaResponse('Admin/People/CreateDuty', [
            'dutyTypes' => Type::where('model_type', Duty::class)->get(),
            'roles' => Role::all(),
            'assignableInstitutions' => DutyService::getInstitutionsForUpserts($this->authorizer),
            'assignableUsers' => User::select('id', 'name', 'profile_photo_path')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDutyRequest $request)
    {
        $duty = new Duty;

        $validatedData = $request->safe();
        $duty->fill(collect($validatedData)->except('types', 'roles', 'current_users')->toArray())->save();

        $duty->types()->sync($request->types);

        $this->handleUsersUpdate(new Collection($duty->current_users->pluck('id')), new Collection($request->current_users), $duty);

        // Load relationships needed for the response
        $duty->load('institution', 'types', 'current_users');

        // Return JSON response for AJAX requests (inline creation in wizard)
        if ($request->wantsJson() || $request->header('X-Inertia-Partial-Data')) {
            return response()->json([
                'success' => true,
                'message' => trans_choice('messages.created', 0, ['model' => trans_choice('entities.duty.model', 1)]),
                'duty' => $duty,
            ]);
        }

        return back()->with('success', trans_choice('messages.created', 0, ['model' => trans_choice('entities.duty.model', 1)]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Duty $duty)
    {
        $this->handleAuthorization('view', $duty);

        $duty->load('institution.tenant', 'users', 'activities.causer', 'types');

        return $this->inertiaResponse('Admin/People/ShowDuty', [
            'duty' => array_merge($duty->toArray(), [
                'sharepointPath' => $duty->institution?->tenant ? $duty->sharepoint_path() : null,
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Duty $duty)
    {
        $this->handleAuthorization('update', $duty);

        $duty->load('institution', 'types', 'roles', 'current_users');

        // Manually load study_program for each user's pivot
        foreach ($duty->current_users as $user) {
            if ($user->pivot && $user->pivot->study_program_id) {
                $user->pivot->load('study_program');
            }
        }

        return $this->inertiaResponse('Admin/People/EditDuty', [
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
     */
    public function update(Request $request, Duty $duty)
    {
        $this->handleAuthorization('update', $duty);

        $request->validate([
            'name' => 'required',
            'current_users' => 'nullable|array',
            'institution_id' => 'required',
            'places_to_occupy' => 'required|numeric',
            'contacts_grouping' => 'required|in:none,study_program,tenant',
            // array of integers
            'types' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $duty) {
            $duty->update($request->only('name', 'description', 'email', 'places_to_occupy', 'contacts_grouping'));

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
     */
    public function destroy(Duty $duty)
    {
        $this->handleAuthorization('delete', $duty);

        $duty->delete();

        return redirect()->route('duties.index')->with('info', trans_choice('messages.deleted', 0, ['model' => trans_choice('entities.duty.model', 1)]));
    }

    public function restore(Duty $duty)
    {
        $this->handleAuthorization('restore', $duty);

        $duty->restore();

        return back()->with('success', 'Pareigybė sėkmingai atkurta!');
    }

    /**
     * Display the duty user update wizard page.
     */
    public function updateUsersWizard()
    {
        $this->handleAuthorization('viewAny', Duty::class);

        // Get institutions the user can access, with their duties and current users
        // Include pivot data (start_date) to detect long-staying users
        $institutions = DutyService::getInstitutionsForUpserts($this->authorizer)
            ->load(['duties' => function ($query) {
                $query->with(['current_users' => function ($q) {
                    $q->select('users.id', 'name', 'email', 'profile_photo_path')
                        ->withPivot('start_date', 'end_date');
                }]);
            }, 'tenant:id,shortname']);

        // Get data needed for creating institutions and duties
        $assignableTenants = GetTenantsForUpserts::execute('institutions.create.padalinys', $this->authorizer);
        $institutionTypes = Type::where('model_type', Institution::class)->get();

        return $this->inertiaResponse('Admin/People/DutyUserUpdateWizard', [
            // Immediate data for Step 1
            'institutions' => $institutions,
            // Data for inline institution creation (small datasets, load immediately)
            'assignableTenants' => $assignableTenants,
            'institutionTypes' => $institutionTypes,
            // Lazy loaded data - only fetched when explicitly requested via router.reload({ only: [...] })
            // Step 3: User assignment
            'users' => Inertia::optional(fn () => User::select('id', 'name', 'email', 'profile_photo_path')
                ->orderBy('name')->get()),
            'studyPrograms' => Inertia::optional(fn () => StudyProgram::select('id', 'name', 'degree', 'tenant_id')->get()),
            // Step 2: Duty creation (only needed if user wants to create a new duty)
            'dutyTypes' => Inertia::optional(fn () => GetAttachableTypesForDuty::execute()->values()),
        ]);
    }

    /**
     * Batch update users for a duty.
     */
    public function batchUpdateUsers(Request $request, Duty $duty)
    {
        $this->handleAuthorization('update', $duty);

        $validated = $request->validate([
            'user_changes' => 'required|array',
            'user_changes.*.user_id' => 'required|string',
            'user_changes.*.action' => 'required|in:add,remove',
            'user_changes.*.start_date' => 'nullable|date',
            'user_changes.*.end_date' => 'nullable|date',
            'user_changes.*.study_program_id' => 'nullable|string|exists:study_programs,id',
            'new_users' => 'nullable|array',
            'new_users.*.name' => 'required|string',
            'new_users.*.email' => 'required|email|unique:users,email',
            'new_users.*.phone' => 'nullable|string',
            'places_to_occupy' => 'nullable|integer|min:1',
        ]);

        $createdUsers = [];

        DB::transaction(function () use ($validated, $duty, &$createdUsers) {
            // Create new users if any
            if (! empty($validated['new_users'])) {
                foreach ($validated['new_users'] as $newUserData) {
                    $user = User::create([
                        'name' => $newUserData['name'],
                        'email' => $newUserData['email'],
                        'phone' => $newUserData['phone'] ?? null,
                    ]);
                    $createdUsers[] = $user;
                }
            }

            // Process user changes
            foreach ($validated['user_changes'] as $change) {
                $userId = $change['user_id'];

                // Skip temporary IDs (new users) - they'll be handled by matching name/email
                if (str_starts_with($userId, 'new-')) {
                    // Find the created user by matching against new_users data
                    $matchingNewUser = collect($createdUsers)->first(function ($user) {
                        // Try to match by position or by the order of creation
                        return true; // We'll attach all created users
                    });

                    if ($matchingNewUser) {
                        $duty->users()->attach($matchingNewUser->id, [
                            'start_date' => $change['start_date'] ?? now(),
                            'end_date' => $change['end_date'] ?? null,
                            'study_program_id' => $change['study_program_id'] ?? null,
                        ]);
                    }

                    continue;
                }

                if ($change['action'] === 'add') {
                    // Check if user is already attached
                    $existingPivot = $duty->dutiables()->where('dutiable_id', $userId)->first();

                    if ($existingPivot) {
                        // Update existing pivot
                        $existingPivot->update([
                            'start_date' => $change['start_date'] ?? now(),
                            'end_date' => $change['end_date'] ?? null,
                            'study_program_id' => $change['study_program_id'] ?? null,
                        ]);
                    } else {
                        // Create new pivot
                        $duty->users()->attach($userId, [
                            'start_date' => $change['start_date'] ?? now(),
                            'end_date' => $change['end_date'] ?? null,
                            'study_program_id' => $change['study_program_id'] ?? null,
                        ]);
                    }
                } elseif ($change['action'] === 'remove') {
                    // Set end_date for removal
                    $duty->users()->updateExistingPivot($userId, [
                        'end_date' => $change['end_date'] ?? now(),
                    ]);
                }
            }

            // Update places_to_occupy if provided
            if (isset($validated['places_to_occupy'])) {
                $duty->update(['places_to_occupy' => $validated['places_to_occupy']]);
            }
        });

        return redirect()->route('duties.show', $duty)
            ->with('success', trans('Pareigybės nariai sėkmingai atnaujinti!'));
    }
}
