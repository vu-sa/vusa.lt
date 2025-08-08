<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Permission;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;

class PermissionController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', Permission::class);

        return $this->inertiaResponse('Admin/Permissions/IndexPermission', [
            'permissions' => Permission::paginate(20),
        ]);
    }
}
