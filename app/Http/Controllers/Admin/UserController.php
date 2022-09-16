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
        $name = request()->input('name');

        $users = User::when(!is_null($name), function ($query) use ($name) {
            $query->where('name', 'like', "%{$name}%")->orWhere('email', 'like', "%{$name}%");
        })->when(!$request->user()->hasRole('Super Admin'), function ($query) {
        })->paginate(20);

        // create lengthawarepaginator manually because need some more filtering
        // $users = new LengthAwarePaginator(
        //     $users->slice($request->page * 20, 20)->values(),
        //     count($users),
        //     20,
        //     $request->page,
        //     [
        //         'path' => $request->url(),
        //         'query' => $request->query()
        //     ]
        // );

        // dd($users, $usersGet);

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
        //
        return Inertia::render('Admin/Contacts/CreateUser', [
            'roles' => Role::all(),
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

        return redirect()->route('users.index');
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
            'roles' => Role::all()
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
        ]);

        // dd($request->all(), auth()->user()->hasRole('Super Admin'));
        
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

        return redirect()->back();
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

        return redirect()->route('users.index');
    }

    public function detachFromDuty(User $user, Duty $duty)
    {
        $user->duties()->detach($duty);
        $user->save();

        return redirect()->back();
    }

    public function storeFromMicrosoft()
    {
        // dd(Socialite::driver('microsoft'));

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
}
