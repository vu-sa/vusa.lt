<?php

namespace App\Http\Requests\Concerns;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use Illuminate\Validation\Rule;

/**
 * The `tenant_id` a seat's quota padalinys is chosen through: only `assignQuota` holders may send
 * it, and only for a padalinys the duty actually offers seats to.
 */
trait ValidatesQuotaTenant
{
    public function canAssignQuota(?Duty $duty): bool
    {
        return $duty !== null && $this->user()->can('assignQuota', [Dutiable::class, $duty]);
    }

    /**
     * @return list<mixed>
     */
    protected function quotaTenantRules(?Duty $duty): array
    {
        if (! $this->canAssignQuota($duty)) {
            return ['prohibited'];
        }

        return ['sometimes', 'nullable', 'integer', Rule::in($duty->assignableTenants()->pluck('tenants.id')->all())];
    }
}
