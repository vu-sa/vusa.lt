<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteUserPassword;
use App\Actions\GenerateUserPassword;
use App\Actions\MergeUsers;
use App\Http\Controllers\AdminController;
use App\Http\Requests\GenerateUserPasswordRequest;
use App\Http\Requests\MergeUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Duty;
use App\Models\Role;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use App\Services\ResourceServices\UserDutyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', User::class);

        $indexer = new ModelIndexer(new User);

        $users = $indexer
            ->setEloquentQuery([
                fn (Builder $query) => $query->with([
                    'duties:id,institution_id',
                    'duties.institution:id,tenant_id',
                    'duties.institution.tenant:id,shortname',
                ])->withCount('duties')])
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        $collection = $users->getCollection()->makeVisible(['last_action']);

        return $this->inertiaResponse('Admin/People/IndexUser', [
            'users' => $users->setCollection($collection),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', User::class);

        return $this->inertiaResponse('Admin/People/CreateUser', [
            'roles' => Role::all(),
            'tenantsWithDuties' => UserDutyService::getTenantsWithDutiesForForm($this->authorizer),
            'permissableTenants' => UserDutyService::getPermissableTenants($this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        DB::transaction(function () use ($request) {
            $user = new User;

            $validatedData = $request->safe();
            $user->fill(collect($validatedData)->except(['current_duties', 'roles'])->toArray());

            $user->save();

            foreach ($request->current_duties as $duty) {
                $user->duties()->attach($duty, ['start_date' => now()->subDay()]);
            }

            // check if user is super admin
            if (User::find(Auth::id())->isSuperAdmin()) {
                if ($request->has('roles')) {
                    $user->roles()->sync($request->roles);
                } else {
                    $user->syncRoles([]);
                }
            }
        });

        return $this->redirectResponse('users.index')->with('success', 'Kontaktas sėkmingai sukurtas!');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->handleAuthorization('view', $user);

        return $this->inertiaResponse('Admin/People/ShowUser', [
            'user' => $user->load(['duties' => function ($query) {
                $query->withPivot('start_date', 'end_date');
            }]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->handleAuthorization('update', $user);

        $user->load('current_duties', 'previous_duties', 'roles');

        return $this->inertiaResponse('Admin/People/EditUser', [
            'user' => $user->makeVisible(['last_action'])->append('has_password')->toFullArray(),
            'roles' => fn () => Role::all(),
            'tenantsWithDuties' => fn () => UserDutyService::getTenantsWithDutiesForForm($this->authorizer),
            'permissableTenants' => UserDutyService::getPermissableTenants($this->authorizer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // TODO: make duty attach / detach work properly
        $this->handleAuthorization('update', $user);

        UserDutyService::syncDutiesForUser(
            new SupportCollection($request->current_duties ?? []),
            $user->current_duties->pluck('id'),
            $user,
            $this->authorizer
        );

        DB::transaction(function () use ($request, $user) {
            $user->update($request->only('name', 'email', 'facebook_url', 'phone', 'profile_photo_path', 'pronouns', 'show_pronouns'));

            // check if user is super admin
            if (User::find(Auth::id())->isSuperAdmin()) {
                if ($request->has('roles')) {
                    $user->roles()->sync($request->roles);
                } else {
                    $user->roles()->sync([]);
                }
            }
        });

        return back()->with('success', 'Kontaktas sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->handleAuthorization('delete', $user);

        $user->delete();

        return $this->redirectResponse('users.index')->with('info', 'Kontaktas sėkmingai ištrintas!');
    }

    /**
     * Show the merge users form.
     */
    public function merge()
    {
        $this->handleAuthorization('merge', User::class);

        $indexer = new ModelIndexer(new User);

        $users = $indexer
            ->setEloquentQuery([
                fn (Builder $query) => $query->with([
                    'duties:id,institution_id',
                    'duties.institution:id,tenant_id',
                    'duties.institution.tenant:id,shortname',
                ])->withCount('duties')])
            ->builder->get();

        return $this->inertiaResponse('Admin/People/MergeUser', [
            'users' => $users,
        ]);
    }

    /**
     * Merge two user accounts.
     */
    public function mergeUsers(MergeUsersRequest $request)
    {
        $keptUser = User::query()->find($request->kept_user_id);
        $mergedUser = User::query()->find($request->merged_user_id);

        MergeUsers::execute($keptUser, $mergedUser);

        return back()->with('success', 'Kontaktai sėkmingai sujungti!');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(User $user, Request $request)
    {
        $this->handleAuthorization('restore', $user);

        $user->restore();

        return back()->with('success', 'Kontaktas sėkmingai atkurtas!');
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete($id, Request $request)
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->handleAuthorization('forceDelete', $user);

        $user->duties()->detach();
        $user->forceDelete();

        return $this->redirectResponse('users.index')->with('success', 'Kontaktas sėkmingai ištrintas!');
    }

    /**
     * Generate a random password for the user (super admin only).
     */
    public function generatePassword(User $user, GenerateUserPasswordRequest $request)
    {
        $password = GenerateUserPassword::execute($user);

        return back()->with('data', $password)
            ->with('success', 'Slaptažodis sėkmingai sukurtas!');
    }

    /**
     * Delete a user's password (super admin only).
     */
    public function deletePassword(User $user, GenerateUserPasswordRequest $request)
    {
        DeleteUserPassword::execute($user);

        return back()->with('success', 'Slaptažodis sėkmingai ištrintas!');
    }
}
