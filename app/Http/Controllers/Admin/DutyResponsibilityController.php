<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\Responsibility;
use App\Enums\ResponsibilityScope;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreDutyResponsibilityRequest;
use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Http\RedirectResponse;

/**
 * Atsakomybės on the duty record: what the duty must handle, next to the roles that say what it
 * may do.
 */
class DutyResponsibilityController extends AdminController
{
    /**
     * The duty's responsibilities and roles, side by side on its record.
     *
     * @return array{items: list<array{id: string, responsibility: string, label: string, scope_type: string, scope_id: string, scope_name: string|null}>, roles: list<array{id: string, name: string}>}
     */
    public static function payload(Duty $duty): array
    {
        return [
            'items' => $duty->responsibilities()
                ->with('scope')
                ->get()
                ->map(fn (DutyResponsibility $assignment) => [
                    'id' => $assignment->id,
                    'responsibility' => $assignment->responsibility->value,
                    'label' => (string) __($assignment->responsibility->labelKey()),
                    'scope_type' => $assignment->scope_type,
                    'scope_id' => $assignment->scope_id,
                    'scope_name' => self::scopeName($assignment),
                ])
                ->values()
                ->all(),
            'roles' => $duty->roles()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($role) => ['id' => (string) $role->getKey(), 'name' => (string) $role->getAttribute('name')])
                ->values()
                ->all(),
        ];
    }

    /**
     * What the add sheet may offer this user: only targets they could assign.
     *
     * @return array{responsibilities: list<array{value: string, label: string, description: string, scopes: list<string>}>, tenants: list<array{id: int, shortname: string}>, types: list<array{id: string, title: string}>, institutions: list<array{id: string, name: string}>}
     */
    public static function options(User $user): array
    {
        $tenants = GetTenantsForUpserts::execute('duties.update.padalinys', app(ModelAuthorizer::class))
            ->map(fn ($tenant) => ['id' => (int) $tenant['id'], 'shortname' => (string) $tenant['shortname']])
            ->values();

        $types = [];

        foreach (InstitutionType::query()->get()->sortBy('title') as $type) {
            if ($user->can('update', $type)) {
                $types[] = ['id' => (string) $type->id, 'title' => (string) $type->title];
            }
        }

        return [
            'responsibilities' => collect(Responsibility::cases())->map(fn (Responsibility $responsibility) => [
                'value' => $responsibility->value,
                'label' => (string) __($responsibility->labelKey()),
                'description' => (string) __("responsibilities.types.{$responsibility->value}.description"),
                'scopes' => array_map(fn (ResponsibilityScope $scope) => $scope->value, $responsibility->allowedScopes()),
            ])->values()->all(),
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
        $duty->responsibilities()->firstOrCreate([
            'responsibility' => $request->responsibility(),
            'scope_type' => $request->scopeType()->value,
            'scope_id' => (string) $request->validated('scope_id'),
        ]);

        return back()->with('success', __('responsibilities.messages.added'));
    }

    public function destroy(Duty $duty, string $responsibility): RedirectResponse
    {
        $this->handleAuthorization('update', $duty);

        // Resolved through the duty so a crafted id cannot reach another duty's assignment.
        $assignment = $duty->responsibilities()->find($responsibility);

        abort_if(! $assignment instanceof DutyResponsibility, 404);

        $assignment->delete();

        return back()->with('success', __('responsibilities.messages.removed'));
    }

    private static function scopeName(DutyResponsibility $assignment): ?string
    {
        $scope = $assignment->scope;

        return match (true) {
            $scope instanceof Tenant => (string) __($scope->shortname),
            $scope instanceof InstitutionType => (string) $scope->title,
            $scope instanceof Institution => (string) $scope->name,
            default => null,
        };
    }
}
