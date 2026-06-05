<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Role;
use App\Services\SystemMonitorService;
use Illuminate\Http\Request;

class SystemStatusController extends AdminController
{
    public function __construct(private SystemMonitorService $monitor) {}

    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', Role::class);

        return $this->inertiaResponse('Admin/SystemStatus', [
            'status' => $this->monitor->getAllStatus(),
            'lastUpdated' => now()->toISOString(),
        ]);
    }
}
