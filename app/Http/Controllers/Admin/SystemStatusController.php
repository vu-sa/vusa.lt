<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SystemMaintenanceAction;
use App\Http\Controllers\AdminController;
use App\Http\Requests\RunSystemMaintenanceRequest;
use App\Models\Role;
use App\Services\DeviceMetricService;
use App\Services\SystemMaintenanceService;
use App\Services\SystemMonitorService;
use Illuminate\Http\RedirectResponse;
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
            'lastUpdated' => now()->toISOString(),
            'status' => $this->monitor->getAllStatus(),
            'deviceMetrics' => $this->deviceMetrics->getRecentMetrics(30),
            'maintenanceActions' => $request->user()->isSuperAdmin()
                ? array_map(fn (SystemMaintenanceAction $action) => [
                    'action' => $action->value,
                    'queued' => $action->isQueued(),
                    'disruptive' => $action->isDisruptive(),
                ], SystemMaintenanceAction::cases())
                : [],
        ]);
    }

    public function runMaintenance(RunSystemMaintenanceRequest $request, SystemMaintenanceService $maintenance): RedirectResponse
    {
        $action = $request->action();

        if (! $maintenance->run($action, $request->user())) {
            return back()->with('error', __('messages.system_maintenance.failed'));
        }

        return back()->with('success', __($action->isQueued()
            ? 'messages.system_maintenance.queued'
            : 'messages.system_maintenance.done'));
    }
}
