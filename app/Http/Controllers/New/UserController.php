<?php

namespace App\Http\Controllers\New;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = User::all();

        return Inertia::render('Admin/Contacts/Index', [
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
        //
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
        //
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

        $user = User::where('email', $microsoftUser->mail)->first();

        if ($user) {

            $user->microsoft_token = $microsoftUser->token;
            $user->update([
                'email_verified_at' => now(),
                // 'image' => $microsoftUser->avatar,
            ]);
        } else {
            $user = new User;
            $user->role_id = 1;
            $user->microsoft_token = $microsoftUser->token;
            $user->name = $microsoftUser->displayName;
            $user->email = $microsoftUser->mail;
            $user->email_verified_at = now();
            $user->save();
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
