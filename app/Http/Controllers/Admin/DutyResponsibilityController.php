<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Actions\Responsibilities\GrantableResponsibilities;
use App\Enums\ResponsibilityScope;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreDutyResponsibilityRequest;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\Permissions\ResponsibilityPermissionGaps;
use App\Support\MorphMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Atsakomybės on the duty record: what the duty does, whether given to it directly, through one of
 * its types (or their ancestors) or through its institution.
 */
class DutyResponsibilityController extends AdminController
{
    /**
     * @return array{items: list<array<string, mixed>>}
     */
    public static function payload(Duty $duty): array
    {
        $duty->loadMissing(['types', 'institution', 'roles.permissions', 'permissions']);
        $gaps = app(ResponsibilityPermissionGaps::class);

        $grants = ResponsibilityGrant::query()
            ->where(fn ($query) => $query
                ->where(fn ($direct) => $direct->where('grantee_type', MorphMap::alias(Duty::class))->where('grantee_id', $duty->id))
                ->orWhere(fn ($viaType) => $viaType->where('grantee_type', MorphMap::alias(DutyType::class))
                    ->whereIn('grantee_id', array_map(strval(...), DutyType::withAncestorIds($duty->types->modelKeys()))))
                ->orWhere(fn ($viaInstitution) => $viaInstitution->where('grantee_type', MorphMap::alias(Institution::class))
                    ->where('grantee_id', $duty->institution_id)))
            ->with(['role', 'grantee', 'scope'])
            ->get();

        return [
            'items' => $grants
                ->sortBy(fn (ResponsibilityGrant $grant): string => ($grant->role->purpose === null ? '1' : '0').$grant->role->displayTitle())
                ->map(fn (ResponsibilityGrant $grant): array => [
                    'id' => $grant->id,
                    'role_id' => $grant->role_id,
                    'title' => $grant->role->displayTitle(),
                    'description' => $grant->role->displayDescription(),
                    'purpose' => $grant->role->purpose?->value,
                    'purpose_label' => $grant->role->purpose ? (string) __($grant->role->purpose->labelKey()) : null,
                    'source' => $grant->grantee_type,
                    'source_name' => $grant->grantee_type === MorphMap::alias(Duty::class) ? null : self::name($grant->grantee),
                    ...$grant->scopePayload(),
                    'missing_permissions' => $grant->role->purpose ? $gaps->missingFor($duty, $grant->role->purpose) : [],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * What the add sheet may offer this user: only responsibilities and targets they could grant.
     *
     * @return array<string, mixed>
     */
    public static function options(User $user): array
    {
        $tenants = GetTenantsForUpserts::execute('duties.update.padalinys', app(ModelAuthorizer::class))
            ->map(fn ($tenant) => ['id' => (int) $tenant['id'], 'shortname' => (string) $tenant['shortname']])
            ->values();

        $types = [];

        foreach (InstitutionType::query()->get()->sortBy('title') as $type) {
            if ($user->can('update', $type)) {
                $types[] = ['id' => (string) $type->id, 'title' => (string) $type->title, 'parent_id' => $type->parent_id === null ? null : (string) $type->parent_id];
            }
        }

        return [
            'responsibilities' => GrantableResponsibilities::for($user)
                ->map(fn (Role $role): array => [
                    'id' => $role->id,
                    'title' => $role->displayTitle(),
                    'description' => $role->displayDescription(),
                    'purpose_label' => $role->purpose ? (string) __($role->purpose->labelKey()) : null,
                    'scopes' => array_map(fn (ResponsibilityScope $scope) => $scope->value, $role->purpose?->allowedScopes() ?? []),
                ])
                ->sortBy('title')
                ->values()
                ->all(),
            'tenants' => $tenants->all(),
            'types' => $types,
            'institutions' => Institution::query()
                ->whereIn('tenant_id', $tenants->pluck('id'))
                ->with('tenant:id,shortname')
                ->get(['id', 'name', 'tenant_id'])
                ->map(fn (Institution $institution) => [
                    'id' => (string) $institution->id,
                    'name' => trim((string) $institution->name.' · '.__((string) $institution->tenant?->shortname), ' ·'),
                ])
                ->sortBy('name')
                ->values()
                ->all(),
        ];
    }

    public function store(StoreDutyResponsibilityRequest $request, Duty $duty): RedirectResponse
    {
        $scopeType = $request->scopeType();
        $duty->responsibilityGrants()->firstOrCreate([
            'role_id' => $request->validated('role_id'),
            'scope_type' => $scopeType?->value,
            'scope_id' => $scopeType ? (string) $request->validated('scope_id') : null,
        ]);

        return back()->with('success', __('responsibilities.messages.added'));
    }

    public function destroy(Duty $duty, string $grant, Request $request): RedirectResponse
    {
        $this->handleAuthorization('update', $duty);

        // Resolved through the duty so a crafted id cannot reach another duty's grant.
        $responsibilityGrant = $duty->responsibilityGrants()->with('role')->find($grant);

        abort_if(! $responsibilityGrant instanceof ResponsibilityGrant, 404);
        abort_unless(GrantableResponsibilities::allows($request->user(), $responsibilityGrant->role), 403);

        if ($warning = $this->guardSelfLockout($request->user(), $this->holds($request->user(), $duty), $request, fn () => $responsibilityGrant->delete())) {
            return $warning;
        }

        return back()->with('success', __('responsibilities.messages.removed'));
    }

    private function holds(User $user, Duty $duty): bool
    {
        return ! $user->isSuperAdmin() && $user->current_duties()->whereKey($duty->getKey())->exists();
    }

    private static function name(mixed $model): ?string
    {
        return match (true) {
            $model instanceof Tenant => (string) __($model->shortname),
            $model instanceof InstitutionType, $model instanceof DutyType => (string) $model->title,
            $model instanceof Institution => (string) $model->name,
            default => null,
        };
    }
}
