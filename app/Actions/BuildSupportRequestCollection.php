<?php

namespace App\Actions;

use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Http\Requests\IndexSupportRequestRequest;
use App\Models\SupportRequest;
use App\Models\SupportRequestArea;
use App\Models\SupportRequestType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildSupportRequestCollection
{
    public function execute(IndexSupportRequestRequest $request): array
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $tab = $request->validated('tab', 'all');
        $allRequests = $this->visibleRequestsFor($user);
        [$tabRequests, $filters] = $this->tabRequests($request, $user);

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

        return [
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
        ];
    }

    /**
     * The list as the page shows it — tab, search and every filter — before sorting and paging.
     *
     * @return Builder<SupportRequest>
     */
    public function query(IndexSupportRequestRequest $request): Builder
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        [$query, $filters] = $this->tabRequests($request, $user);
        $this->applyDashboardFilters($query, $filters, includeStatus: true);

        return $query;
    }

    /**
     * The visible requests of the chosen tab with every filter but status applied.
     *
     * @return array{0: Builder<SupportRequest>, 1: array<string, mixed>}
     */
    private function tabRequests(IndexSupportRequestRequest $request, User $user): array
    {
        $allRequests = $this->visibleRequestsFor($user);
        $tabRequests = $request->validated('tab', 'all') === 'mine'
            ? (clone $allRequests)->where('created_by', $user->id)
            : clone $allRequests;
        $filters = $request->getFilters();
        $search = $request->validated('search');
        if (is_string($search) && $search !== '') {
            $filters['search'] = $search;
        }

        $this->applyDashboardFilters($tabRequests, $filters, includeStatus: false);

        return [$tabRequests, $filters];
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
     * @param  array<int, mixed>  $sorting
     */
    private function applySorting(Builder $query, array $sorting): void
    {
        $columns = ['title', 'status', 'created_at'];
        $sort = $sorting[0] ?? null;
        $sortId = is_array($sort) ? ($sort['id'] ?? null) : null;
        $column = in_array($sortId, $columns, true) ? $sortId : 'created_at';

        $query->orderBy($column, is_array($sort) && ($sort['desc'] ?? true) === false ? 'asc' : 'desc');
    }
}
