<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexSupportRequestRequest;
use App\Http\Requests\StoreSupportRequestRequest;
use App\Models\Role;
use App\Models\SupportRequest;
use App\Models\SupportRequestArea;
use App\Models\SupportRequestType;
use App\Models\SupportService;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class MySupportRequestController extends AdminController
{
    public function index(IndexSupportRequestRequest $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $tab = $request->validated('tab', 'all');
        $allRequests = $this->visibleRequestsFor($user);
        $tabRequests = $tab === 'mine'
            ? (clone $allRequests)->where('created_by', $user->id)
            : clone $allRequests;
        $filters = $request->getFilters();

        $this->applyDashboardFilters($tabRequests, $filters, includeStatus: false);

        $statusCounts = collect(SupportRequestStatus::cases())
            ->mapWithKeys(fn (SupportRequestStatus $status): array => [$status->value => 0]);

        (clone $tabRequests)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->each(fn (int $count, string $status) => $statusCounts->put($status, $count));

        $this->applyDashboardFilters($tabRequests, $filters, includeStatus: true);
        $this->applySorting($tabRequests, $request->getSorting());

        $requests = $tabRequests
            ->with([
                'creator:id,name,profile_photo_path',
                'assignedTo:id,name,profile_photo_path',
                'type:id,name',
                'area:id,name',
            ])
            ->withCount('comments')
            ->paginate($request->getPerPage())
            ->withQueryString();

        $assignees = User::query()
            ->whereIn('id', (clone $allRequests)->whereNotNull('assigned_to')->select('assigned_to'))
            ->orderBy('name')
            ->get(['id', 'name', 'profile_photo_path']);

        return $this->inertiaResponse('Admin/Dashboard/ShowSupportRequests', [
            'requests' => $requests,
            'currentTab' => $tab,
            'tabCounts' => [
                'all' => (clone $allRequests)->count(),
                'mine' => (clone $allRequests)->where('created_by', $user->id)->count(),
            ],
            'statusCounts' => $statusCounts,
            'filters' => $filters,
            'sorting' => $request->getSorting(),
            'types' => SupportRequestType::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'areas' => SupportRequestArea::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'assignees' => $assignees,
            'statusOptions' => collect(SupportRequestStatus::cases())->map(fn (SupportRequestStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'badgeVariant' => $status->badgeVariant(),
            ]),
        ]);
    }

    private function visibleRequestsFor(User $user): Builder
    {
        $query = SupportRequest::query();

        if ($user->can('viewAny', SupportRequest::class)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('created_by', $user->id)
                ->orWhere('visibility', SupportRequestVisibility::Public)
                ->orWhere(function (Builder $query) use ($user): void {
                    $query->where('visibility', SupportRequestVisibility::Roles)
                        ->whereHas('roles', function (Builder $query) use ($user): void {
                            $query->whereHas('users', fn (Builder $query) => $query->whereKey($user->id))
                                ->orWhereHas('currentUsersThroughDuties', fn (Builder $query) => $query->whereKey($user->id));
                        });
                });
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDashboardFilters(Builder $query, array $filters, bool $includeStatus): void
    {
        $search = $filters['search'] ?? null;
        if (is_string($search) && $search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reporter_name', 'like', "%{$search}%");
            });
        }

        $this->whereInWhenPresent($query, 'support_request_type_id', $filters['type'] ?? null);
        $this->whereInWhenPresent($query, 'support_request_area_id', $filters['area'] ?? null);
        $this->whereInWhenPresent($query, 'assigned_to', $filters['assigned_to'] ?? null);

        if ($includeStatus) {
            $this->whereInWhenPresent($query, 'status', $filters['status'] ?? null);
        }
    }

    private function whereInWhenPresent(Builder $query, string $column, mixed $values): void
    {
        if (is_array($values) && $values !== []) {
            $query->whereIn($column, $values);
        } elseif (is_string($values) && $values !== '') {
            $query->where($column, $values);
        }
    }

    /**
     * @param  array<int, array{id: string, desc: bool}>  $sorting
     */
    private function applySorting(Builder $query, array $sorting): void
    {
        $columns = ['title', 'status', 'created_at'];
        $sort = $sorting[0] ?? ['id' => 'created_at', 'desc' => true];
        $column = in_array($sort['id'] ?? null, $columns, true) ? $sort['id'] : 'created_at';

        $query->orderBy($column, ($sort['desc'] ?? true) ? 'desc' : 'asc');
    }

    public function create()
    {
        $this->authorize('create', SupportRequest::class);

        return $this->inertiaResponse('Admin/SupportRequests/CreateSupportRequest', $this->formOptions());
    }

    public function store(StoreSupportRequestRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['roles', 'images']);
        $area = SupportRequestArea::query()->findOrFail($data['support_request_area_id']);

        $supportRequest = SupportRequest::create([
            ...$data,
            'support_service_id' => $area->support_service_id,
            'created_by' => $request->user()->id,
            'locale' => app()->getLocale(),
        ]);

        if ($request->validated('visibility') === 'roles') {
            $supportRequest->roles()->sync($request->validated('roles', []));
        }

        foreach ($request->file('images', []) as $image) {
            $supportRequest->addMedia($image)->toMediaCollection('evidence');
        }

        return redirect()->route('supportRequests.show', $supportRequest)->with('success', __('messages.feedback.thanks'));
    }

    public static function formOptions(?User $user = null, ?SupportRequest $supportRequest = null): array
    {
        $user ??= auth()->user();

        $roleQuery = Role::query();

        if ($user && ! $user->hasRole(config('permission.super_admin_role_name'))) {
            $existingRoleIds = $supportRequest?->roles->pluck('id') ?? collect();

            $roleQuery->where(function ($query) use ($user, $existingRoleIds): void {
                $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                    ->orWhereHas('duties', fn ($q) => $q->whereIn('duties.id', $user->current_duties()->select('duties.id')));

                if ($existingRoleIds->isNotEmpty()) {
                    $query->orWhereIn('roles.id', $existingRoleIds);
                }
            });
        }

        $roles = $roleQuery
            ->with([
                'users:users.id,users.name,users.profile_photo_path',
                'currentUsersThroughDuties:users.id,users.name,users.profile_photo_path',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'users' => $role->users
                    ->concat($role->currentUsersThroughDuties)
                    ->unique('id')
                    ->map(fn (User $u): array => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'profile_photo_path' => $u->profile_photo_path,
                    ])
                    ->values()
                    ->all(),
            ]);

        return [
            'types' => SupportRequestType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'areas' => SupportRequestArea::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'roles' => $roles,
            'service' => SupportService::query()->where('slug', 'vusa-lt')->first(),
        ];
    }
}
