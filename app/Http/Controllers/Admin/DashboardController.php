<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $roles = auth()->user()->getRoleNames();

        return Inertia::render('Admin/ShowDashboard', [
            'roles' => $roles,
        ]);
    }
}
