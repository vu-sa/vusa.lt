<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportRequestStatus;
use App\Http\Controllers\AdminController;
use App\Models\NotificationDigestQueue;
use App\Models\Role;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\SystemMonitorService;
use Inertia\Inertia;

/**
 * Sistema's overview (PR 7.4): what the people who run the platform need to look at first.
 *
 * Every number is omitted for someone who may not open the list behind it, so the page never
 * offers a link that would 403.
 */
class SistemaDashboardController extends AdminController
{
    /** The checks whose failure is an operator's problem; integrations being unconfigured is not. */
    private const array CORE_CHECKS = ['redis', 'database', 'cache', 'typesense', 'scheduler', 'digest', 'mail'];

    private const int LIST_SIZE = 5;

    public function __construct(private readonly SystemMonitorService $monitor) {}

    public function index()
    {
        $this->authorizeWorkspace('sistema');

        $user = request()->user();

        $canSeeRequests = $user->can('viewAny', SupportRequest::class);
        $canSeeRoles = $user->can('viewAny', Role::class);

        return $this->inertiaResponse('Admin/Dashboard/ShowSistema', [
            'counts' => [
                'openRequests' => $canSeeRequests ? SupportRequest::query()->open()->count() : null,
                'queuedMail' => $canSeeRoles ? NotificationDigestQueue::query()->count() : null,
                'roles' => $canSeeRoles ? Role::query()->count() : null,
                'users' => $user->can('viewAny', User::class) ? User::query()->count() : null,
            ],
            'newRequests' => $canSeeRequests ? $this->newRequests() : [],
            // The monitor probes Redis, Typesense and the mailer over the network, so it never blocks the first paint.
            'problems' => Inertia::defer(fn (): array => $canSeeRoles ? $this->problems() : [], 'secondary'),
        ]);
    }

    /**
     * @return list<array{id: string, title: string, reporter: string|null, created_at: string|null}>
     */
    private function newRequests(): array
    {
        return SupportRequest::query()
            ->where('status', SupportRequestStatus::New)
            ->latest()
            ->take(self::LIST_SIZE)
            ->get()
            ->map(fn (SupportRequest $request): array => [
                'id' => (string) $request->id,
                'title' => (string) $request->title,
                'reporter' => $request->reporter_name,
                'created_at' => $request->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{check: string, status: string}>
     */
    private function problems(): array
    {
        $status = $this->monitor->getAllStatus();

        return collect(self::CORE_CHECKS)
            ->map(fn (string $check): array => ['check' => $check, 'status' => (string) ($status[$check]['status'] ?? 'error')])
            ->reject(fn (array $result): bool => $result['status'] === 'healthy')
            ->values()
            ->all();
    }
}
