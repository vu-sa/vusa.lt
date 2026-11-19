<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexPermissionRequest;
use App\Models\Permission;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    #[Authorize('viewAny', Permission::class)]
    public function index(IndexPermissionRequest $request): Response
    {
        // A short list sent whole: the collection searches, sorts and filters it in the browser.
        return Inertia::render('Admin/Permissions/IndexPermission', [
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name', 'created_at', 'updated_at']),
        ]);
    }
}
