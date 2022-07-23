<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $role = auth()->user()->role;

        return Inertia::render('Admin/ShowDashboard', [
            'role' => $role,
        ]);
    }
}
