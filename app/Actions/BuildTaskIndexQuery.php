<?php

namespace App\Actions;

use App\Http\Requests\IndexTasksRequest;
use App\Models\Task;
use App\Models\User;
use App\Services\ModelAuthorizer;
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
        $tenantFilter = $request->filled('tenant') ? (array) $request->validated('tenant') : null;
        $query = self::base($scope, $user, $authorizer, $tenantFilter)->withListingRelations();

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

        $sort = $request->getSorting()[0] ?? null;
        if ($sort && in_array($sort['id'] ?? null, ['created_at', 'updated_at'], true)) {
            return $query->orderBy($sort['id'], ($sort['desc'] ?? false) ? 'desc' : 'asc')->orderBy('id');
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
     * Every task the scope may show in the selected padaliniai.
     *
     * @param  array<int, int|string>|null  $tenantFilter
     * @return Builder<Task>
     */
    public static function base(string $scope, User $user, ModelAuthorizer $authorizer, ?array $tenantFilter = null): Builder
    {
        return $scope === self::SCOPE_TENANT
            ? self::tenantScope($user, $authorizer, $tenantFilter)
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
     * @param  array<int, int|string>|null  $tenantFilter
     * @return Builder<Task>
     */
    private static function tenantScope(User $user, ModelAuthorizer $authorizer, ?array $tenantFilter): Builder
    {
        $tenantIds = self::readableTenantIds($user, $authorizer);
        if ($tenantFilter !== null) {
            $tenantIds = $tenantIds->intersect($tenantFilter);
        }

        $taskableTenantIds = [];
        foreach (Task::TENANT_TASKABLE_PERMISSIONS as $type => $permission) {
            $taskableTenantIds[$type] = $authorizer->scope($user, $permission)->tenantIds()->all();
        }

        return Task::query()->inTenants($tenantIds->all(), $taskableTenantIds);
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
