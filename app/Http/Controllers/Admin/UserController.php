<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SendWelcomeEmail;
use App\Http\Controllers\Controller;
use App\Http\Requests\MergeUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\Duty;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

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

        return Inertia::render('Admin/People/IndexUser', [
            'users' => $users->setCollection($collection),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', User::class);

        $permissableTenants = User::find(Auth::id())->hasRole(config('permission.super_admin_role_name')) ? Tenant::all() : $this->authorizer->getTenants();

        return Inertia::render('Admin/People/CreateUser', [
            'roles' => Role::all(),
            'tenantsWithDuties' => $this->getDutiesForForm($this->authorizer),
            'permissableTenants' => $permissableTenants,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        DB::transaction(function () use ($request) {
            // create user

            $user = new User;

            $user->fill($request->safe()->except(['current_duties', 'roles']));

            $user->save();

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
        $this->authorize('view', $user);

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
        $this->authorize('update', $user);

        // user load duties with pivot
        $user->load('current_duties', 'previous_duties', 'roles');

        $permissableTenants = User::find(Auth::id())->hasRole(config('permission.super_admin_role_name')) ? Tenant::all() : $this->authorizer->getTenants();

        return Inertia::render('Admin/People/EditUser', [
            'user' => $user->makeVisible(['last_action'])->toFullArray(),
            // get all roles
            'roles' => fn () => Role::all(),
            'tenantsWithDuties' => fn () => $this->getDutiesForForm($this->authorizer),
            'permissableTenants' => $permissableTenants,
        ]);
    }

    public function sendWelcomeEmail(User $user)
    {
        $this->authorize('update', $user);

        SendWelcomeEmail::execute((new Collection)->push($user));

        return back()->with('success', 'Laiškas sėkmingai išsiųstas!');
    }

    public function renderWelcomeEmail(User $user)
    {
        $this->authorize('update', $user);

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
        $this->authorize('update', $user);

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'roles' => 'array',
        ]);

        $this->handleDutiesUpdate((new SupportCollection($request->current_duties)), $user->current_duties->pluck('id'), $user);

        DB::transaction(function () use ($request, $user) {
            $user->update($request->only('name', 'email', 'facebook_url', 'phone', 'profile_photo_path', 'pronouns', 'show_pronouns'));

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
        // Check if not super admin
        if (User::find(Auth::id())->hasRole(config('permission.super_admin_role_name'))) {
            $permissableTenants = Tenant::all();
        } else {
            $permissableTenants = $this->authorizer->getTenants();
        }

        $new = $existing_duties->diff($user_duties)->values();
        $deleted = $user_duties->diff($existing_duties)->values();
        // attach new duties

        foreach ($new as $duty) {

            $duty = Duty::find($duty);

            if (! $permissableTenants->contains($duty->institution->tenant)) {
                continue;
            }

            $user->duties()->attach($duty, ['start_date' => now()->subDay()]);
        }

        // update duty end date of deleted duties
        foreach ($deleted as $duty) {

            $duty = Duty::find($duty);

            if (! $permissableTenants->contains($duty->institution->tenant)) {
                continue;
            }

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
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')->with('info', 'Kontaktas sėkmingai ištrintas!');
    }

    private function getDutiesForForm($authorizer)
    {
        // return Duty::with(['institution:id,name,tenant_id', 'institution.tenant:id,shortname'])
        // ->when(!auth()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) {
        //     $query->whereHas('institution', function ($query) {
        //         $query->where('tenant_id', User::find(Auth::id())->tenant()?->id);
        //     });
        // })->get();

        if (! $authorizer->forUser(Auth::user())->checkAllRoleables('users.create.all')) {
            return Tenant::orderBy('shortname')->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
                ->whereIn('id', User::find(Auth::id())->tenants->pluck('id'))->get();
        } else {
            return Tenant::orderBy('shortname')->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')->get();
        }
    }

    public function storeFromMicrosoft(Request $request)
    {
        $microsoftUser = Socialite::driver('microsoft')->stateless()->user();

        /*dd(Socialite::driver('microsoft')->stateless());*/

        // pirmiausia ieškome per vartotoją, per paštą
        $user = User::where('email', $microsoftUser->email)->first();

        if ($user) {
            // jei randama per vartotojo paštą, prijungiam

            // if user role is null, add role
            $user->microsoft_token = $microsoftUser->token;

            $user->save();

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $duty = Duty::where('email', $microsoftUser->email)->first();

        if ($duty) {
            //# TEST: if only current users from duty are allowed to login

            // get count of current users
            $count = $duty->current_users()->count();

            if ($count > 1) {
                return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()])->with('error', 'Nepavyko prisijungti su pareigybiniu paštu, nes pareigybinis paštas turi daugiau nei vieną aktyvų vartotoją. Susisiekite su administratoriumi.');
            }

            $user = $duty->current_users()->first();

            if (! $user) {
                return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()])->with('error', 'Nepavyko prisijungti su pareigybiniu paštu, nes pareigybinis paštas neturi aktyvaus vartotojo. Bandykite ištrinti slapukus arba naudoti naršyklės privatų rėžimą.');
            }

            $user->microsoft_token = $microsoftUser->token;

            $user->save();

            Auth::login($user);

            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()]);
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

    public function merge()
    {
        $this->authorize('merge', User::class);

        $indexer = new ModelIndexer(new User);

        $users = $indexer
            ->setEloquentQuery([
                fn (Builder $query) => $query->with([
                    'duties:id,institution_id',
                    'duties.institution:id,tenant_id',
                    'duties.institution.tenant:id,shortname',
                ])->withCount('duties')])
            ->builder->get(['id', 'name', 'email']);

        return Inertia::render('Admin/People/MergeUser', [
            'users' => $users,
        ]);
    }

    public function mergeUsers(MergeUsersRequest $request)
    {
        $keptUser = User::query()->find($request->kept_user_id);

        $mergedUser = User::query()->find($request->merged_user_id);

        DB::transaction(function () use ($keptUser, $mergedUser) {
            // transfer duties, doings, tasks, memberships, reservations
            foreach ($mergedUser->duties as $duty) {
                $mergedUser->duties()->updateExistingPivot($duty->id, ['dutiable_id' => $keptUser->id]);
            }

            // TODO: some how manage mergeable relationships
            $mergedUser->doings()->update(['user_id' => $keptUser->id]);

            $mergedUser->tasks()->update(['user_id' => $keptUser->id]);

            $mergedUser->memberships()->update(['user_id' => $keptUser->id]);

            $mergedUser->reservations()->update(['user_id' => $keptUser->id]);

            $mergedUser->forceDelete();
        });

        return back()->with('success', 'Kontaktai sėkmingai sujungti!');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()]);
    }

    public function restore(User $user, Request $request)
    {
        $this->authorize('restore', $user);

        $user->restore();

        return back()->with('success', 'Kontaktas sėkmingai atkurtas!');
    }

    public function forceDelete($id, Request $request)
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $user);

        $user->duties()->detach();
        $user->forceDelete();

        return redirect()->route('users.index')->with('success', 'Kontaktas sėkmingai ištrintas!');
    }
}
