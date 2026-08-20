<?php

namespace App\Rules;

use App\Actions\GetTenantsForUpserts;
use App\Services\ModelAuthorizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Validates that a request-supplied parent id belongs to a tenant the acting user may act in.
 *
 * `HasCommonChecks::create()` is tenant-agnostic — it only asks whether the user holds
 * `{resource}.create.padalinys` *somewhere* — so a store endpoint that reads its parent from
 * the payload is otherwise wide open across tenants. This rule closes that by resolving the
 * parent's `tenant_id` and comparing it against the user's authorized scope for a permission.
 *
 * A missing parent is left to the accompanying `exists` rule so the error points at the real
 * problem instead of reading as an authorization failure.
 *
 * Precedent for the underlying check: StoreDutyRequest::withValidator().
 */
class WithinAuthorizedTenantScope implements ValidationRule
{
    /**
     * @param  class-string<Model>  $parentModel  The model the id refers to.
     * @param  string  $permission  e.g. 'meetings.create.padalinys'
     */
    public function __construct(
        protected string $parentModel,
        protected string $permission,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tenantId = $this->resolveTenantId($value);

        if ($tenantId === null) {
            return;
        }

        $allowedTenantIds = GetTenantsForUpserts::execute($this->permission, app(ModelAuthorizer::class))
            ->pluck('id')
            ->map(intval(...))
            ->all();

        if (! in_array((int) $tenantId, $allowedTenantIds, true)) {
            $fail(__('validation.outside_tenant_scope'));
        }
    }

    /**
     * Read the parent's tenant, including soft-deleted parents so a trashed record reports as
     * out of scope rather than silently passing.
     */
    private function resolveTenantId(mixed $value): int|string|null
    {
        return $this->parentModel::query()
            // Equivalent to withTrashed(), but callable on a plain Builder — the model class
            // is only known at runtime here. Harmless on models without SoftDeletes.
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereKey($value)
            ->value('tenant_id');
    }
}
