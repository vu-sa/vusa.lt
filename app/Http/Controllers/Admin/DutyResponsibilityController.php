<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Responsibilities\GrantableResponsibilities;
use App\Enums\ResponsibilityPurpose;
use App\Enums\ResponsibilityScope;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreDutyResponsibilityRequest;
use App\Models\Duty;
use App\Models\ResponsibilityGrant;
use App\Models\User;
use App\Services\DutyResponsibilityPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Atsakomybės on the duty record: what the duty does, whether given to it directly, through one of
 * its types (or their ancestors) or through its institution.
 */
class DutyResponsibilityController extends AdminController
{
    /** @return array{items: list<array{id: string, role_id: string, title: string, description: string|null, purpose: ResponsibilityPurpose|null, purpose_label: string|null, source: 'duty'|'duty_type'|'institution', source_name: string|null, scope_type: ResponsibilityScope|'own_tenant'|null, scope_name: string|null, missing_permissions: list<string>}>} */
    public static function payload(Duty $duty): array
    {
        return DutyResponsibilityPresenter::payload($duty);
    }

    /** @return array{responsibilities: list<array{id: string, title: string, description: string|null, purpose_label: string|null, scopes: list<ResponsibilityScope>}>, tenants: list<array{id: int, shortname: string}>, types: list<array{id: string, title: string, parent_id: string|null}>, institutions: list<array{id: string, name: string}>} */
    public static function options(User $user): array
    {
        return DutyResponsibilityPresenter::options($user);
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
}
