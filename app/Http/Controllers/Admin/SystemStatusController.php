<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Role;
use App\Services\DeviceMetricService;
use App\Services\SystemMonitorService;

class SystemStatusController extends AdminController
{
    public function __construct(
        private readonly SystemMonitorService $monitor,
        private readonly DeviceMetricService $deviceMetrics,
    ) {}

    public function index()
    {
        $this->handleAuthorization('viewAny', Role::class);

        return $this->inertiaResponse('Admin/SystemStatus', [
            'lastUpdated' => now()->toISOString(),
            'status' => $this->monitor->getAllStatus(),
            'deviceMetrics' => $this->deviceMetrics->getRecentMetrics(30),
        ]);
    }
}
