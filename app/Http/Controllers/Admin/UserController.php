<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SendWelcomeEmail;
use App\Http\Controllers\LaravelResourceController;
use App\Models\Duty;
use App\Models\Padalinys;
use App\Models\Role;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class UserController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [User::class, $this->authorizer]);

        $indexer = new ModelIndexer(new User(), request(), $this->authorizer);

        $users = $indexer
            ->setEloquentQuery([
                fn (Builder $query) => $query->with([
                    'duties:id,institution_id',
                    'duties.institution:id,padalinys_id',
                    'duties.institution.padalinys:id,shortname',
                ])->withCount('duties')])
            ->filterAllColumns()
            ->sortAllColumns()
            ->onlyTrashed($request->input('showSoftDeleted') === 'true')
            ->builder->paginate(20);

        return Inertia::render('Admin/People/IndexUser', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [User::class, $this->authorizer]);

        return Inertia::render('Admin/People/CreateUser', [
            'roles' => Role::all(),
            'padaliniaiWithDuties' => $this->getDutiesForForm($this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [User::class, $this->authorizer]);

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'current_duties' => 'required',
        ]);

        DB::transaction(function () use ($request) {
            // create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'profile_photo_path' => $request->profile_photo_path,
            ]);

            foreach ($request->current_duties as $duty) {
                $user->duties()->attach($duty, ['start_date' => now()->subDay()]);
            }

            // check if user is super admin
            if (User::find(Auth::id())->hasRole(config('permission.super_admin_role_name'))) {
                // check if user is super admin
                if ($request->has('roles')) {
                    $user->roles()->sync($request->roles);
                } else {
                    $user->syncRoles([]);
                }
            }
        });

        return redirect()->route('users.index')->with('success', 'Kontaktas sėkmingai sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $this->authorize('view', [User::class, $user, $this->authorizer]);

        return Inertia::render('Admin/People/ShowUser', [
            'user' => $user->load(['duties' => function ($query) {
                $query->withPivot('start_date', 'end_date');
            }]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $this->authorize('update', [User::class, $user, $this->authorizer]);

        // user load duties with pivot
        $user->load('current_duties', 'previous_duties', 'roles');

        return Inertia::render('Admin/People/EditUser', [
            'user' => $user->makeVisible(['last_action']),
            // get all roles
            'roles' => fn () => Role::all(),
            'padaliniaiWithDuties' => fn () => $this->getDutiesForForm($this->authorizer),
        ]);
    }

    public function sendWelcomeEmail(User $user)
    {
        $this->authorize('update', [User::class, $user, $this->authorizer]);

        SendWelcomeEmail::execute((new Collection())->push($user));

        return back()->with('success', 'Laiškas sėkmingai išsiųstas!');
    }

    public function renderWelcomeEmail(User $user)
    {
        $this->authorize('update', [User::class, $user, $this->authorizer]);

        return new \App\Mail\WelcomeEmail($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // TODO: make duty attach / detach work properly
        $this->authorize('update', [User::class, $user, $this->authorizer]);

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'roles' => 'array',
        ]);

        $this->handleDutiesUpdate((new SupportCollection($request->current_duties)), $user->current_duties->pluck('id'), $user);

        DB::transaction(function () use ($request, $user) {
            $user->update($request->only('name', 'email', 'phone', 'profile_photo_path'));

            // handle duties update

            // check if user is super admin
            if (User::find(Auth::id())->hasRole(config('permission.super_admin_role_name'))) {
                // check if user is super admin
                if ($request->has('roles')) {
                    $user->roles()->sync($request->roles);
                } else {
                    $user->roles()->sync([]);
                }
            }
        });

        return back()->with('success', 'Kontaktas sėkmingai atnaujintas!');
    }

    // TODO: doesn't account for duties with the same name
    private function handleDutiesUpdate(SupportCollection $existing_duties, SupportCollection $user_duties, User $user)
    {
        $new = $existing_duties->diff($user_duties)->values();
        $deleted = $user_duties->diff($existing_duties)->values();
        // attach new duties

        foreach ($new as $duty) {
            $user->duties()->attach($duty, ['start_date' => now()->subDay()]);
        }

        // update duty end date of deleted duties
        foreach ($deleted as $duty) {
            $user->duties()->updateExistingPivot($duty, ['end_date' => now()->subDay()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', [User::class, $user, $this->authorizer]);

        $user->delete();

        return redirect()->route('users.index')->with('info', 'Kontaktas sėkmingai ištrintas!');
    }

    private function getDutiesForForm($authorizer)
    {
        // return Duty::with(['institution:id,name,padalinys_id', 'institution.padalinys:id,shortname'])
        // ->when(!auth()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) {
        //     $query->whereHas('institution', function ($query) {
        //         $query->where('padalinys_id', User::find(Auth::id())->padalinys()?->id);
        //     });
        // })->get();

        if (! $authorizer->forUser(Auth::user())->checkAllRoleables('users.create.all')) {
            return Padalinys::orderBy('shortname')->with('institutions:id,name,padalinys_id', 'institutions.duties:id,name,institution_id')
                ->whereIn('id', User::find(Auth::id())->padaliniai->pluck('id'))->get();
        } else {
            return Padalinys::orderBy('shortname')->with('institutions:id,name,padalinys_id', 'institutions.duties:id,name,institution_id')->get();
        }
    }

    public function storeFromMicrosoft()
    {
        $microsoftUser = Socialite::driver('microsoft')->stateless()->user();

        // pirmiausia ieškome per vartotoją, per paštą
        $user = User::where('email', $microsoftUser->email)->first();

        if ($user) {
            // jei randama per vartotojo paštą, prijungiam

            // if user role is null, add role
            $user->microsoft_token = $microsoftUser->token;

            Auth::login($user);
            request()->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $duty = Duty::where('email', $microsoftUser->email)->first();

        if ($duty) {
            $user = $duty->users()->first();
            $user->microsoft_token = $microsoftUser->token;

            Auth::login($user);

            request()->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return redirect()->route('home');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return back()->withErrors([
            'email' => __('auth.failed'),
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home', ['padalinys' => 'www']);
    }

    public function restore($id, Request $request)
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->authorize('restore', [User::class, $user, $this->authorizer]);

        $user->restore();

        return redirect()->route('users.index')->with('success', 'Kontaktas sėkmingai atkurtas!');
    }

    public function forceDelete($id, Request $request)
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', [User::class, $user, $this->authorizer]);

        $user->duties()->detach();
        $user->forceDelete();

        return redirect()->route('users.index')->with('success', 'Kontaktas sėkmingai ištrintas!');
    }
}
