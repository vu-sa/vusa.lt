<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Inertia\Inertia;
use App\Services\ModelAuthorizer as Authorizer;

class PermissionController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', Permission::class);

        return Inertia::render('Admin/Permissions/IndexPermission', [
            'permissions' => Permission::paginate(20),
        ]);
    }
}
