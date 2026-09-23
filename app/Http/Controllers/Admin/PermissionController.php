<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexPermissionRequest;
use App\Models\Permission;
use Inertia\Response;

class PermissionController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexPermissionRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Permission::class);

        // A short list sent whole: the collection searches, sorts and filters it in the browser.
        return $this->inertiaResponse('Admin/Permissions/IndexPermission', [
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name', 'created_at', 'updated_at']),
        ]);
    }
}
