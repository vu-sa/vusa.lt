<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // load user duty institutions
        $user = User::find(auth()->user()->id);
        $dutyInstitutions = $user->duties->pluck('institution')->flatten()->unique();

        // convert to eloquent collection
        $dutyInstitutions = new EloquentCollection($dutyInstitutions);

        $dutyInstitutions->load('users:users.id,profile_photo_path');

        return Inertia::render('Admin/ShowDashboard', [
            'dutyInstitutions' => $dutyInstitutions,
        ]);
    }

    public function userSettings()
    {
        $user = User::find(auth()->user()->id);
        
        return Inertia::render('Admin/ShowUserSettings', [
            'roles' => $user->getRoleNames(),
        ]);
    }
}
