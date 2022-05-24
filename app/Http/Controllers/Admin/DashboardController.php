<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $role = auth()->user()->role;

        return Inertia::render('Admin/Dashboard', [
            'role' => $role,
        ]);
    }
}
