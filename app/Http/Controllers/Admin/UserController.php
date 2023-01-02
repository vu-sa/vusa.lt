<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Duty;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // For search
        $name = request()->input('name');

        $users = User::
        when(!is_null($name), function ($query) use ($name) {
            $query->where('name', 'like', "%{$name}%")->orWhere('email', 'like', "%{$name}%");
        })->
            when(!$request->user()->hasRole('Super Admin'), function ($query) {
                $query->whereHas('duties.institution', function ($query) {
                    $query->where('padalinys_id', Auth::user()->padalinys()->id);
                });
        })->with(['duties:id,institution_id', 'duties.institution:id,padalinys_id','duties.institution.padalinys:id,shortname'])
        ->paginate(20);

        return Inertia::render('Admin/Contacts/IndexUsers', [
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
        return Inertia::render('Admin/Contacts/CreateUser', [
            'roles' => Role::all(),
            'duties' => $this->getDutiesForForm()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'duties' => 'required',
            'email' => 'required',
        ]);

        DB::transaction(function () use ($request) {
            $user = new User();

            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->profile_photo_path = $request->profile_photo_path;

            $user->save();

            foreach ($request->duties as $duty) {
                $user->duties()->attach($duty);
            }

            // check if user is super admin
            if (auth()->user()->hasRole('Super Admin')) {
                // check if user is super admin
                if ($request->has('roles')) {
                    $user->syncRoles($request->roles);
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
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        // user load duties with pivot

        return Inertia::render('Admin/Contacts/EditUser', [
            'contact' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'phone' => $user->phone,
                'roles' => $user->getRoleNames(),
                'duties' => $user->duties->map(function ($duty) {
                    return [
                        'id' => $duty->id,
                        'email' => $duty->email,
                        'name' => $duty->name,
                        'institution' => $duty->institution,
                        'pivot' => $duty->pivot,
                        'type' => $duty->type,
                    ];
                }),
            ],
            // get all roles
            'roles' => Role::all(),
            'duties' => $this->getDutiesForForm()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'duties' => 'required',
        ]);

        DB::transaction(function () use ($request, $user) {

            $user->update($request->only('name', 'email', 'phone', 'profile_photo_path'));
            $user->duties()->sync($request->duties);

            // check if user is super admin
            if (auth()->user()->hasRole('Super Admin')) {
                // check if user is super admin
                if ($request->has('roles')) {
                    $user->syncRoles($request->roles);
                } else {
                    $user->syncRoles([]);
                }
            }

        });

        return back()->with('success', 'Kontaktas sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //cuser 
        $user->duties()->detach();
        $user->delete();

        return redirect()->route('users.index')->with('info', 'Kontaktas sėkmingai ištrintas!');
    }

    private function getDutiesForForm()
    {
        return Duty::with(['institution:id,padalinys_id', 'institution.padalinys:id,shortname'])
        ->when(!auth()->user()->hasRole('Super Admin'), function ($query) { 
            $query->whereHas('institution', function ($query) {
                $query->where('padalinys_id', Auth::user()->padalinys()?->id);
            });
        })->get();
    }

    public function detachFromDuty(User $user, Duty $duty)
    {
        $this->authorize('detachFromDuty', [auth()->user(), $user]);

        $user->duties()->detach($duty);
        $user->save();

        return back()->with('info', 'Kontaktas sėkmingai atjungtas nuo pareigos!');
    }

    public function storeFromMicrosoft()
    {
        $microsoftUser = Socialite::driver('microsoft')->user();

        // check if microsoft user mail contains 'vusa.lt'
        if (strpos($microsoftUser->email, 'vusa.lt') == true) {

            // pirmiausia ieškome per vartotoją, per paštą
            $user = User::where('email', $microsoftUser->mail)->first();

            if ($user) {
                // jei randama per vartotojo paštą, prijungiam

                // if user role is null, add role
                $user->microsoft_token = $microsoftUser->token;
                $user->update([
                    'email_verified_at' => now(),
                    // 'image' => $microsoftUser->avatar,
                ]);

            } else {

                // jei nerandama per vartotojo paštą, ieškome per pareigybės paštą
                $duty = Duty::where('email', $microsoftUser->mail)->first();

                if ($duty) {
                    $user = $duty->users()->first();
                    $user->microsoft_token = $microsoftUser->token;
                    $user->update([
                        'email_verified_at' => now(),
                        // 'image' => $microsoftUser->avatar,
                    ]);

                } else {

                    $user = new User;

                    $user->microsoft_token = $microsoftUser->token;
                    $user->name = $microsoftUser->displayName;
                    $user->email = $microsoftUser->mail;
                    $user->email_verified_at = now();
                    $user->save();
                }

            }

            Auth::login($user);

            return redirect()->route('dashboard');
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
 
            return redirect()->intended('dashboard');
        }
 
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
    
        $request->session()->invalidate();
    
        $request->session()->regenerateToken();
    
        return redirect()->route('main.home');
    }
}
