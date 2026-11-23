<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesDutyTypeAssignments;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Rules\SoftDeleteRules;
use App\Rules\WithinAuthorizedTenantScope;
use App\Services\ModelAuthorizer;
use App\Support\MorphMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDutyRequest extends FormRequest
{
    use ValidatesDutyTypeAssignments;

    protected function prepareForValidation(): void
    {
        if (! $this->exists('types')) {
            $this->merge(['types' => $this->route('duty')->types()->pluck('duty_types.id')->all()]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Duty $duty */
        $duty = $this->route('duty');

        return [
            'name' => 'required',
            // Both are persisted by DutyController::update() and previously had no rule at
            // all. `description` is rich text, sanitized on write by HasTranslations.
            'description' => 'nullable',
            'email' => 'nullable|email',
            'current_users' => 'nullable|array',
            'institution_id' => [
                'bail', 'required', 'ulid', SoftDeleteRules::existsLive('institutions'),
                ...($this->input('institution_id') !== $duty->institution_id
                    ? [new WithinAuthorizedTenantScope(Institution::class, 'duties.update.padalinys', $this->user())]
                    : []),
            ],
            'places_to_occupy' => 'sometimes|nullable|integer|min:1',
            'contacts_grouping' => 'required|in:none,study_program,tenant',
            'is_active' => 'sometimes|boolean',
            'former_names' => 'sometimes|array',
            'former_names.*.name' => 'required|array',
            'former_names.*.name.lt' => 'required|string|max:255',
            'former_names.*.name.en' => 'nullable|string|max:255',
            'former_names.*.used_until' => 'required|date|before_or_equal:today',
            'types' => 'nullable|array',
            'types.*' => ['integer', 'distinct', SoftDeleteRules::existsLive('duty_types')],
            'ex_officio_target_duty_ids' => 'nullable|array',
            'ex_officio_target_duty_ids.*' => ['ulid', 'distinct', SoftDeleteRules::existsLive('duties'), 'not_in:'.$duty->id],
            'assignable_tenants' => 'nullable|array',
            'assignable_tenants.*' => 'required|array',
            'assignable_tenants.*.tenant_id' => 'required|integer|distinct|exists:tenants,id',
            'assignable_tenants.*.quota' => 'nullable|integer|min:1',
            'assignable_tenants.*.user_ids' => 'nullable|array',
            'assignable_tenants.*.user_ids.*' => ['string', SoftDeleteRules::existsLive('users')],
        ];
    }

    /**
     * Holders of an active ex-officio seat on this duty, keyed by the tenant they represent.
     *
     * @return array<int, array<int, string>>
     */
    private function exOfficioUserIdsByTenant(Duty $duty): array
    {
        return Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereNotNull('via_dutiable_id')
            ->whereNotNull('tenant_id')
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            })
            ->get(['tenant_id', 'dutiable_id'])
            ->groupBy('tenant_id')
            ->map(fn ($rows) => $rows->pluck('dutiable_id')->map(fn ($id) => (string) $id)->all())
            ->all();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var Duty $duty */
            $duty = $this->route('duty');

            $this->authorizeDutyTypeChanges($v, $duty->types()->pluck('duty_types.id')->all());

            // Retiring a duty must not quietly strand the people still holding it.
            if ($this->has('is_active') && ! $this->boolean('is_active') && $duty->current_users()->exists()) {
                $v->errors()->add('is_active', __('Pirmiau užbaik narių pareigas, tada pažymėk pareigybę nebeveikiančia.'));
            }

            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $authorizer = app(ModelAuthorizer::class);
            $hasGlobalDutyScope = $authorizer->allows($this->user(), 'duties.update.*');
            $assignableTenants = collect($this->input('assignable_tenants') ?? []);

            if (! $hasGlobalDutyScope) {
                $targetIds = array_filter((array) $this->input('ex_officio_target_duty_ids', []));

                if ($targetIds) {
                    $institutionId = $this->input('institution_id');
                    $sourceTenantId = $institutionId === null
                        ? $duty->institution?->tenant_id
                        : Institution::whereKey($institutionId)->value('tenant_id');

                    if (Duty::whereIn('id', $targetIds)
                        ->whereHas('institution', fn ($q) => $q->where('tenant_id', '!=', $sourceTenantId))
                        ->exists()) {
                        $v->errors()->add('ex_officio_target_duty_ids', __('Ex-officio pareigos turi priklausyti tam pačiam padaliniui.'));
                    }
                }

                $editableTenantIds = $authorizer->tenants($this->user(), 'duties.update.padalinys')->pluck('id');
                [$assignableTenants, $lockedRows] = $assignableTenants->partition(
                    fn (array $row) => $editableTenantIds->contains((int) $row['tenant_id']),
                );
                $this->validateLockedAssignableTenants($v, $duty, $editableTenantIds->all(), $lockedRows->all());
            }

            // Enforce per-tenant quota against the requested user_ids count, plus the
            // seats the tenant already holds ex officio — those are granted by another
            // duty and never appear in the picker, but they do fill the tenant's places.
            $exOfficioUserIdsByTenant = $this->exOfficioUserIdsByTenant($duty);

            foreach ($assignableTenants as $i => $row) {
                $quota = $row['quota'] ?? null;

                if ($quota === null) {
                    continue;
                }

                $exOfficioUserIds = $exOfficioUserIdsByTenant[$row['tenant_id'] ?? null] ?? [];
                $userIds = array_map(strval(...), (array) ($row['user_ids'] ?? []));
                $occupied = count(array_unique([...$userIds, ...$exOfficioUserIds]));

                if ($occupied > (int) $quota) {
                    $v->errors()->add("assignable_tenants.$i.user_ids", $exOfficioUserIds === []
                        ? __('Padalinio kvota (:quota) viršyta.', ['quota' => $quota])
                        : __('Padalinio kvota (:quota) viršyta, įskaitant ex officio narius.', ['quota' => $quota]));
                }
            }
        });
    }

    private function validateLockedAssignableTenants(Validator $validator, Duty $duty, array $editableTenantIds, array $rows): void
    {
        $lockedTenants = $duty->loadMissing('assignableTenants')->assignableTenants
            ->whereNotIn('id', $editableTenantIds)->keyBy('id');
        $message = __('validation.outside_tenant_scope');

        if ($lockedTenants->keys()->diff(array_column($rows, 'tenant_id'))->isNotEmpty()) {
            $validator->errors()->add('assignable_tenants', $message);
        }

        $tenantIdsWithUserLists = $lockedTenants->keys()->intersect(array_column(
            array_filter($rows, fn (array $row) => array_key_exists('user_ids', $row)),
            'tenant_id',
        ));
        $currentReps = $tenantIdsWithUserLists->isEmpty() ? collect() : $duty->dutiables()
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereIn('tenant_id', $tenantIdsWithUserLists)
            ->whereNull('via_dutiable_id')
            ->notEnded()
            ->get(['tenant_id', 'dutiable_id'])
            ->groupBy('tenant_id');

        foreach ($rows as $i => $row) {
            $tenantId = (int) $row['tenant_id'];
            $tenant = $lockedTenants->get($tenantId);
            $quota = isset($row['quota']) ? (int) $row['quota'] : null;
            $currentQuota = $tenant?->getRelationValue('pivot')?->quota;

            if ($tenant === null || $quota !== ($currentQuota === null ? null : (int) $currentQuota)) {
                $validator->errors()->add("assignable_tenants.$i.tenant_id", $message);

                continue;
            }

            if (array_key_exists('user_ids', $row)) {
                $submitted = $row['user_ids'] ?? [];
                $current = $currentReps->get($tenantId, collect())->pluck('dutiable_id')->all();

                if (array_diff($submitted, $current) !== [] || array_diff($current, $submitted) !== []) {
                    $validator->errors()->add("assignable_tenants.$i.tenant_id", $message);
                }
            }
        }
    }
}
