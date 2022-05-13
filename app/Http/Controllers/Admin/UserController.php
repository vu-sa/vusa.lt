<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Duty;
use App\Http\Controllers\Controller as Controller;

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
        $users = User::paginate(20);

        return Inertia::render('Admin/Contacts/Users/Index', [
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
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        return Inertia::render('Admin/Contacts/Users/Edit', [
            'contact' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'duties' => $user->duties->map(function ($duty) {
                        return [
                            'id' => $duty->id,
                            'name' => $duty->name,
                            'institution' => $duty->institution,
                        ];
                    }),
                    'role' => $user->role ?? "",
                ]
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
        $user->update($request->only('name', 'email', 'phone'));

        // get all user duties and delete all of them
        $user->duties()->detach();

        // dd($user->duties);

        // add new roles
        foreach ($request->duties as $duty) {
            $user->duties()->attach($duty);
        }

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
        //
    }

    public function storeFromMicrosoft()
    {
        $microsoftUser = Socialite::driver('microsoft')->user();

        // check if microsoft user mail contains 'vusa.lt'
        if (strpos($microsoftUser->email, 'vusa.lt') == true) {

        $user = User::where('email', $microsoftUser->mail)->first();

        if ($user) {

            $user->microsoft_token = $microsoftUser->token;
            $user->update([
                'email_verified_at' => now(),
                // 'image' => $microsoftUser->avatar,
            ]);
        } else {
            
            $duty = Duty::where('email', $microsoftUser->mail)->first();

            if ($duty) {
                $user = $duty->users()->first();
                $user->microsoft_token = $microsoftUser->token;
                $user->update([
                    'email_verified_at' => now(),
                    // 'image' => $microsoftUser->avatar,
                ]);
            } else {

                return redirect()->route('home');
            
            // $user = new User;
            // $user->role_id = 2;
            // $user->microsoft_token = $microsoftUser->token;
            // $user->name = $microsoftUser->displayName;
            // $user->email = $microsoftUser->mail;
            // $user->email_verified_at = now();
            // $user->save();
        }

        // } else {
        //     return redirect()->route('home');
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
}