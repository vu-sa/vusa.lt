<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Role;
use App\Services\DeviceMetricService;
use App\Services\SystemMonitorService;
use Illuminate\Http\Request;

class SystemStatusController extends AdminController
{
    public function __construct(
        private readonly SystemMonitorService $monitor,
        private readonly DeviceMetricService $deviceMetrics,
    ) {}

    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', Role::class);

        return $this->inertiaResponse('Admin/SystemStatus', [
            'status' => $this->monitor->getAllStatus(),
            'lastUpdated' => now()->toISOString(),
            'deviceMetrics' => $this->deviceMetrics->getRecentMetrics(30),
        ]);
    }
}
