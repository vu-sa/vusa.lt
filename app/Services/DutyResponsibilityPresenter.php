<?php

namespace App\Services;

use App\Actions\GetTenantsForUpserts;
use App\Actions\Responsibilities\GrantableResponsibilities;
use App\Enums\ResponsibilityPurpose;
use App\Enums\ResponsibilityScope;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Permissions\ResponsibilityPermissionGaps;
use App\Support\MorphMap;

class DutyResponsibilityPresenter
{
    /**
     * @return array{items: list<array{id: string, role_id: string, title: string, description: string|null, purpose: ResponsibilityPurpose|null, purpose_label: string|null, source: 'duty'|'duty_type'|'institution', source_name: string|null, scope_type: ResponsibilityScope|'own_tenant'|null, scope_name: string|null, missing_permissions: list<string>}>}
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
                    'purpose' => $grant->role->purpose,
                    'purpose_label' => $grant->role->purpose ? (string) __($grant->role->purpose->labelKey()) : null,
                    'source' => $grant->grantee_type,
                    'source_name' => $grant->grantee_type === MorphMap::alias(Duty::class) ? null : self::name($grant->grantee),
                    'scope_type' => $grant->coversOwnTenant() ? ResponsibilityGrant::OWN_TENANT : $grant->coverage(),
                    'scope_name' => $grant->scopePayload()['scope_name'],
                    'missing_permissions' => $grant->role->purpose ? $gaps->missingFor($duty, $grant->role->purpose) : [],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * What the add sheet may offer this user: only responsibilities and targets they could grant.
     *
     * @return array{responsibilities: list<array{id: string, title: string, description: string|null, purpose_label: string|null, scopes: list<ResponsibilityScope>}>, tenants: list<array{id: int, shortname: string}>, types: list<array{id: string, title: string, parent_id: string|null}>, institutions: list<array{id: string, name: string}>}
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
                    'scopes' => $role->purpose?->allowedScopes() ?? [],
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
