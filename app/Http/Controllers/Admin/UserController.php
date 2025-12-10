<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SendWelcomeEmail;
use App\Http\Controllers\AdminController;
use App\Http\Requests\GenerateUserPasswordRequest;
use App\Http\Requests\MergeUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\InstitutionCheckIn;
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
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

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

        $permissableTenants = User::find(Auth::id())->hasRole(config('permission.super_admin_role_name')) ? Tenant::all() : $this->authorizer->getTenants();

        return $this->inertiaResponse('Admin/People/CreateUser', [
            'roles' => Role::all(),
            'tenantsWithDuties' => $this->getDutiesForForm($this->authorizer),
            'permissableTenants' => $permissableTenants,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        DB::transaction(function () use ($request) {
            // create user

            $user = new User;

            $validatedData = $request->safe();
            $user->fill(collect($validatedData)->except(['current_duties', 'roles'])->toArray());

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

        // user load duties with pivot
        $user->load('current_duties', 'previous_duties', 'roles');

        $permissableTenants = User::find(Auth::id())->hasRole(config('permission.super_admin_role_name')) ? Tenant::all() : $this->authorizer->getTenants();

        return $this->inertiaResponse('Admin/People/EditUser', [
            'user' => $user->makeVisible(['last_action'])->append('has_password')->toFullArray(),
            // get all roles
            'roles' => fn () => Role::all(),
            'tenantsWithDuties' => fn () => $this->getDutiesForForm($this->authorizer),
            'permissableTenants' => $permissableTenants,
        ]);
    }

    public function sendWelcomeEmail(User $user)
    {
        $this->handleAuthorization('update', $user);

        SendWelcomeEmail::execute((new Collection)->push($user));

        return $this->redirectBackWithSuccess('Laiškas sėkmingai išsiųstas!');
    }

    public function renderWelcomeEmail(User $user)
    {
        $this->handleAuthorization('update', $user);

        return new \App\Mail\WelcomeEmail($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // TODO: make duty attach / detach work properly
        $this->handleAuthorization('update', $user);

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
     */
    public function destroy(User $user)
    {
        $this->handleAuthorization('delete', $user);

        $user->delete();

        return $this->redirectResponse('users.index')->with('info', 'Kontaktas sėkmingai ištrintas!');
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
        // Handle OAuth errors (e.g., user cancelled login)
        if ($request->has('error')) {
            $error = $request->get('error');
            $errorSubcode = $request->get('error_subcode');

            \Log::info('Microsoft OAuth flow cancelled or denied', [
                'error' => $error,
                'error_subcode' => $errorSubcode,
                'error_description' => $request->get('error_description'),
                'user_ip' => $request->ip(),
            ]);

            // User cancelled the login - redirect gracefully
            if ($error === 'access_denied' || $errorSubcode === 'cancel') {
                $message = app()->getLocale() === 'en'
                    ? 'Login was cancelled. Please try again if you wish to sign in.'
                    : 'Prisijungimas buvo atšauktas. Bandykite dar kartą, jei norite prisijungti.';

                return redirect()->route('login')->with('status', $message);
            }

            // Other OAuth errors
            $message = app()->getLocale() === 'en'
                ? 'An error occurred during login. Please try again.'
                : 'Prisijungimo metu įvyko klaida. Bandykite dar kartą.';

            return redirect()->route('login')->with('error', $message);
        }

        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            // Log the error for debugging
            \Log::warning('Microsoft OAuth InvalidStateException, retrying with stateless', [
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
                'referer' => $request->headers->get('referer'),
            ]);

            // Retry with stateless method
            /** @phpstan-ignore-next-line */
            $microsoftUser = Socialite::driver('microsoft')->stateless()->user();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Handle Guzzle HTTP errors (e.g., 400 Bad Request from token exchange)
            \Log::error('Microsoft OAuth ClientException', [
                'message' => $e->getMessage(),
                'user_ip' => $request->ip(),
            ]);

            $message = app()->getLocale() === 'en'
                ? 'Login failed. Please try again.'
                : 'Prisijungimas nepavyko. Bandykite dar kartą.';

            return redirect()->route('login')->with('error', $message);
        } catch (\Exception $e) {
            // Catch any other unexpected exceptions
            \Log::error('Microsoft OAuth unexpected error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_ip' => $request->ip(),
            ]);

            $message = app()->getLocale() === 'en'
                ? 'An unexpected error occurred. Please try again.'
                : 'Įvyko netikėta klaida. Bandykite dar kartą.';

            return redirect()->route('login')->with('error', $message);
        }

        // pirmiausia ieškome per vartotoją, per paštą
        $user = User::where('email', $microsoftUser->getEmail())->first();

        if ($user) {
            // jei randama per vartotojo paštą, prijungiam

            // if user role is null, add role
            $user->microsoft_token = $microsoftUser->token ?? null;

            $user->save();

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $duty = Duty::where('email', $microsoftUser->getEmail())->first();

        if ($duty) {
            // # TEST: if only current users from duty are allowed to login

            // get count of current users
            $count = $duty->current_users()->count();

            if ($count > 1) {
                return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()])->with('error', 'Nepavyko prisijungti su pareigybiniu paštu, nes pareigybinis paštas turi daugiau nei vieną aktyvų vartotoją. Susisiekite su administratoriumi.');
            }

            $user = $duty->current_users()->first();

            if (! $user) {
                return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()])->with('error', 'Nepavyko prisijungti su pareigybiniu paštu, nes pareigybinis paštas neturi aktyvaus vartotojo. Bandykite ištrinti slapukus arba naudoti naršyklės privatų rėžimą.');
            }

            /** @var \App\Models\User $user */
            $user->microsoft_token = $microsoftUser->token ?? null;

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

    public function mergeUsers(MergeUsersRequest $request)
    {
        $keptUser = User::query()->find($request->kept_user_id);

        $mergedUser = User::query()->find($request->merged_user_id);

        DB::transaction(function () use ($keptUser, $mergedUser) {
            // transfer duties, tasks, memberships, reservations
            foreach ($mergedUser->duties as $duty) {
                $mergedUser->duties()->updateExistingPivot($duty->id, ['dutiable_id' => $keptUser->id]);
            }

            // TODO: some how manage mergeable relationships

            $mergedUser->tasks()->update(['user_id' => $keptUser->id]);

            $mergedUser->memberships()->update(['user_id' => $keptUser->id]);

            $mergedUser->reservations()->update(['user_id' => $keptUser->id]);

            // Transfer comments to the kept user
            Comment::query()->where('user_id', $mergedUser->id)->update(['user_id' => $keptUser->id]);

            // Transfer institution check-ins to the kept user
            InstitutionCheckIn::query()->where('user_id', $mergedUser->id)->update(['user_id' => $keptUser->id]);

            // Transfer training participations to the kept user
            DB::table('training_user')
                ->where('user_id', $mergedUser->id)
                ->update(['user_id' => $keptUser->id]);

            // Transfer training organizer role to the kept user
            DB::table('trainings')
                ->where('organizer_id', $mergedUser->id)
                ->update(['organizer_id' => $keptUser->id]);

            $mergedUser->forceDelete();
        });

        return back()->with('success', 'Kontaktai sėkmingai sujungti!');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Cleanly return the user to homepage, without inertia
        return back()->with('success', 'Sėkmingai atsijungta!');
    }

    public function restore(User $user, Request $request)
    {
        $this->handleAuthorization('restore', $user);

        $user->restore();

        return back()->with('success', 'Kontaktas sėkmingai atkurtas!');
    }

    public function forceDelete($id, Request $request)
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->handleAuthorization('forceDelete', $user);

        $user->duties()->detach();
        $user->forceDelete();

        return $this->redirectResponse('users.index')->with('success', 'Kontaktas sėkmingai ištrintas!');
    }

    /**
     * Generate a random password for the user (super admin only)
     */
    public function generatePassword(User $user, GenerateUserPasswordRequest $request)
    {
        // Generate a random password with 10 characters
        $password = Str::random(10);

        // Update the user's password
        $user->password = bcrypt($password);
        $user->save();

        // Return the one-time visible password
        return back()->with('data', $password)
            ->with('success', 'Slaptažodis sėkmingai sukurtas!');
    }

    /**
     * Delete a user's password (super admin only)
     */
    public function deletePassword(User $user, GenerateUserPasswordRequest $request)
    {
        // Set password to null
        $user->password = null;
        $user->save();

        return back()->with('success', 'Slaptažodis sėkmingai ištrintas!');
    }
}
