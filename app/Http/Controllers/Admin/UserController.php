<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Duty;
use App\Http\Controllers\Controller as Controller;
use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        })->when(!User::find(Auth::id())->isAdmin(), function ($query) {
            // join with institutions table and padali
            // $query->addSelect(['institution_id' => Duty::select('institution_id')]);
            // $query->rightJoin('duties_institutions', 'duties_institutions.id', '=', 'duties.institution_id');
            // })->paginate(20);
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
            'email' => 'email|unique:users|unique:duties',
        ]);

        DB::transaction(function () use ($request) {
            $user = new User();

            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->role_id = $request->role_id;
            $user->profile_photo_path = $request->profile_photo_path;

            $user->save();

            if (User::find(Auth::user()->id)->isAdmin()) {
                $user->role_id = $request->role['id'];
                $user->save();
            }

            foreach ($request->duties as $duty) {
                $user->duties()->attach($duty);
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
                'duties' => $user->duties->map(function ($duty) {
                    return [
                        'id' => $duty->id,
                        'name' => $duty->name,
                        'institution' => $duty->institution,
                    ];
                }),
                'role' => $user->role,
            ],
            'roles' => Role::all(),
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
        DB::transaction(function () use ($request, $user) {
            // dd($request->all());

            $user->update($request->only('name', 'email', 'phone', 'profile_photo_path'));

            if (User::find(Auth::user()->id)->isAdmin()) {

                // if admin, they can edit role. but not everyone has a role assigned and sometimes it lead to a bug, 
                // this automatically assigned a least permissive role
                $role = $request->role !== [] ? $request->role : ['id' => Role::where('alias', 'naudotojai')->first()->id];

                $user->role_id = $role['id'];
                $user->save();
                // TODO: role revamp with Spatie permissions or smth
            }

            // get all user duties and delete all of them
            $user->duties()->detach();

            // dd($user->duties);

            // add new roles
            foreach ($request->duties as $duty) {
                $user->duties()->attach($duty);
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
            $standardRole = Role::where('name', 'vartotojas')->first();

            if ($user) {
                // jei randama per vartotojo paštą, prijungiam

                // if user role is null, add role
                $user->microsoft_token = $microsoftUser->token;
                $user->update([
                    'email_verified_at' => now(),
                    // 'image' => $microsoftUser->avatar,
                ]);

                if ($user->role_id == null) {
                    $user->role()->associate($standardRole);
                    $user->save();
                }
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

                    // if user role is null, add role

                    if ($user->role_id == null) {
                        $user->role()->associate($standardRole);
                        $user->save();
                    }
                } else {

                    $user = new User;
                    $user->role_id = $standardRole->id;

                    $user->microsoft_token = $microsoftUser->token;
                    $user->name = $microsoftUser->displayName;
                    $user->email = $microsoftUser->mail;
                    $user->email_verified_at = now();
                    $user->save();
                }

                // } else {
                //     return redirect()->route('home');
            }

            Auth::login($user);

            return redirect()->route('dashboard');
        }

        return redirect()->route('home');
    }
}
