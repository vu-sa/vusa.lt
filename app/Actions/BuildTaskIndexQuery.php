<?php

namespace App\Actions;

use App\Http\Requests\IndexTasksRequest;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The one task listing behind both doors: `mine` (Mano → Užduotys) is what is assigned to the
 * user, `tenant` (ViSAK → Užduotys) is every task in the padaliniai they may read.
 */
final class BuildTaskIndexQuery
{
    public const string SCOPE_MINE = 'mine';

    public const string SCOPE_TENANT = 'tenant';

    /**
     * The scope with every filter applied except completion, so counts can be taken from it.
     *
     * @return Builder<Task>
     */
    public static function scoped(IndexTasksRequest $request, string $scope, User $user, ModelAuthorizer $authorizer): Builder
    {
        $query = self::base($scope, $user, $authorizer);

        $query->with(['taskable', 'users:id,name,email,profile_photo_path']);

        if ($scope === self::SCOPE_TENANT && $request->filled('tenant')) {
            $tenantIds = collect((array) $request->validated('tenant'))->map(fn ($id) => (int) $id)
                ->intersect(self::readableTenantIds($user, $authorizer));

            // Tenants the user may not read narrow to nothing rather than being ignored silently.
            $query->whereHas('tenants', fn (Builder $q) => $q->whereIn('tenants.id', $tenantIds->all()));
        }

        if ($types = $request->validated('taskable_type')) {
            $query->whereIn('taskable_type', (array) $types);
        }

        if ($scope === self::SCOPE_TENANT && $request->validated('assigned') === 'me') {
            $query->whereHas('users', fn (Builder $q) => $q->where('users.id', $user->id));
        }

        if ($request->boolean('overdue')) {
            self::overdue($query);
        }

        if ($request->boolean('auto')) {
            self::automatic($query);
        }

        if ($search = trim((string) $request->validated('search'))) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query;
    }

    /**
     * @return Builder<Task>
     */
    public static function execute(IndexTasksRequest $request, string $scope, User $user, ModelAuthorizer $authorizer): Builder
    {
        $query = self::scoped($request, $scope, $user, $authorizer);
        $completion = $request->completion();

        match ($completion) {
            'completed' => $query->whereNotNull('completed_at'),
            'pending' => $query->whereNull('completed_at'),
            default => null,
        };

        if ($request->sortsBy('created_at')) {
            return $query->latest('created_at');
        }

        if ($completion === 'completed') {
            return $query->latest('completed_at');
        }

        return $query
            ->orderByRaw('completed_at IS NOT NULL')
            ->orderByRaw('CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 0 ELSE 1 END', [now()])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->latest('created_at');
    }

    /**
     * Chip counts over the scope, ignoring the chips' own filters so each count stays what its
     * chip would show.
     *
     * @return array{pending: int, overdue: int, auto: int, assigned: int, completed: int}
     */
    public static function counts(string $scope, User $user, ModelAuthorizer $authorizer): array
    {
        $base = self::base($scope, $user, $authorizer);

        $pending = (clone $base)->whereNull('completed_at');

        return [
            'pending' => (clone $pending)->count(),
            'overdue' => self::overdue(clone $pending)->count(),
            'auto' => self::automatic(clone $pending)->count(),
            'assigned' => $scope === self::SCOPE_TENANT
                ? (clone $pending)->whereHas('users', fn (Builder $q) => $q->where('users.id', $user->id))->count()
                : (clone $pending)->count(),
            'completed' => (clone $base)->whereNotNull('completed_at')->count(),
        ];
    }

    /**
     * Every task the scope may show, before any filter.
     *
     * @return Builder<Task>
     */
    public static function base(string $scope, User $user, ModelAuthorizer $authorizer): Builder
    {
        return $scope === self::SCOPE_TENANT
            ? self::tenantScope($user, $authorizer)
            : Task::query()->whereHas('users', fn (Builder $q) => $q->where('users.id', $user->id));
    }

    /**
     * @return Collection<int, int>
     */
    public static function readableTenantIds(User $user, ModelAuthorizer $authorizer): Collection
    {
        return $authorizer->tenants($user, 'tasks.read.padalinys')->pluck('id')->map(fn ($id) => (int) $id);
    }

    /**
     * Tasks in the user's readable padaliniai whose subject they may also read — a task never
     * reveals a meeting, reservation or institution the user could not open themselves.
     *
     * @return Builder<Task>
     */
    private static function tenantScope(User $user, ModelAuthorizer $authorizer): Builder
    {
        $taskTenantIds = self::readableTenantIds($user, $authorizer);
        $meetingTenantIds = $authorizer->tenants($user, 'meetings.read.padalinys')->pluck('id');
        $reservationTenantIds = $authorizer->tenants($user, 'reservations.read.padalinys')->pluck('id');
        $institutionTenantIds = $authorizer->tenants($user, 'institutions.read.padalinys')->pluck('id');

        return Task::query()
            ->whereHas('tenants', fn (Builder $q) => $q->whereIn('tenants.id', $taskTenantIds->all()))
            ->where(function (Builder $q) use ($meetingTenantIds, $reservationTenantIds, $institutionTenantIds): void {
                if ($meetingTenantIds->isNotEmpty()) {
                    $q->orWhere(fn (Builder $sub) => $sub
                        ->where('taskable_type', MorphMap::alias(Meeting::class))
                        ->whereHasMorph('taskable', [Meeting::class], fn (Builder $meeting) => $meeting
                            ->whereHas('tenants', fn (Builder $tenant) => $tenant->whereIn('tenants.id', $meetingTenantIds))));
                }

                if ($reservationTenantIds->isNotEmpty()) {
                    $q->orWhere(fn (Builder $sub) => $sub
                        ->where('taskable_type', MorphMap::alias(Reservation::class))
                        ->whereHasMorph('taskable', [Reservation::class], fn (Builder $reservation) => $reservation
                            ->whereHas('tenants', fn (Builder $tenant) => $tenant->whereIn('tenants.id', $reservationTenantIds))));
                }

                // A task outlives a hard-deleted subject. Nothing is left to authorize against and
                // the tenant filter already scopes it, so it stays listed where it can be cleared.
                $q->orWhere(fn (Builder $sub) => $sub->whereDoesntHaveMorph('taskable', Task::TASKABLE_TYPES));

                if ($institutionTenantIds->isNotEmpty()) {
                    $q->orWhere(fn (Builder $sub) => $sub
                        ->where('taskable_type', MorphMap::alias(Institution::class))
                        ->whereHasMorph('taskable', [Institution::class], fn (Builder $institution) => $institution
                            ->whereHas('tenant', fn (Builder $tenant) => $tenant->whereIn('tenants.id', $institutionTenantIds))));
                }
            });
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private static function overdue(Builder $query): Builder
    {
        return $query->whereNull('completed_at')->whereNotNull('due_date')->where('due_date', '<', now());
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private static function automatic(Builder $query): Builder
    {
        return $query->whereNotNull('action_type')->where('action_type', '!=', ActionType::Manual->value);
    }
}
