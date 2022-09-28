<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // load user duty institutions
        $user = User::find(auth()->user()->id);
        $dutyInstitutions = $user->duties->pluck('institution')->flatten()->unique();

        return Inertia::render('Admin/ShowDashboard', [
            'roles' => $user->getRoleNames(),
            'dutyInstitutions' => $dutyInstitutions,
        ]);
    }
}
