<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Rules\SoftDeleteRules;
use App\Services\ModelAuthorizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDutyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('duty'));
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
            'current_users' => 'nullable|array',
            'institution_id' => 'required',
            'places_to_occupy' => 'required|numeric',
            'contacts_grouping' => 'required|in:none,study_program,tenant',
            'types' => 'nullable|array',
            'ex_officio_target_duty_ids' => 'nullable|array',
            'ex_officio_target_duty_ids.*' => ['ulid', 'distinct', SoftDeleteRules::existsLive('duties'), 'not_in:'.$duty->id],
            'assignable_tenants' => 'nullable|array',
            'assignable_tenants.*.tenant_id' => 'required|integer|exists:tenants,id',
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
            ->where('dutiable_type', User::class)
            ->whereNotNull('via_dutiable_id')
            ->whereNotNull('tenant_id')
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
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

            $authorizer = app(ModelAuthorizer::class)->forUser($this->user());
            $hasGlobalDutyScope = $authorizer->check('duties.update.*');

            if (! $hasGlobalDutyScope) {
                $sourceTenantId = $duty->institution?->tenant_id;
                $targetIds = array_filter((array) $this->input('ex_officio_target_duty_ids', []));

                if ($targetIds && Duty::whereIn('id', $targetIds)
                    ->whereHas('institution', fn ($q) => $q->where('tenant_id', '!=', $sourceTenantId))
                    ->exists()) {
                    $v->errors()->add('ex_officio_target_duty_ids', __('Ex-officio pareigos turi priklausyti tam pačiam padaliniui.'));
                }
            }

            // Enforce per-tenant quota against the requested user_ids count, plus the
            // seats the tenant already holds ex officio — those are granted by another
            // duty and never appear in the picker, but they do fill the tenant's places.
            $exOfficioUserIdsByTenant = $this->exOfficioUserIdsByTenant($duty);

            foreach ((array) $this->input('assignable_tenants', []) as $i => $row) {
                $quota = $row['quota'] ?? null;

                if ($quota === null) {
                    continue;
                }

                $exOfficioUserIds = $exOfficioUserIdsByTenant[$row['tenant_id'] ?? null] ?? [];
                $userIds = array_map('strval', (array) ($row['user_ids'] ?? []));
                $occupied = count(array_unique([...$userIds, ...$exOfficioUserIds]));

                if ($occupied > (int) $quota) {
                    $v->errors()->add("assignable_tenants.$i.user_ids", $exOfficioUserIds === []
                        ? __('Padalinio kvota (:quota) viršyta.', ['quota' => $quota])
                        : __('Padalinio kvota (:quota) viršyta, įskaitant ex officio narius.', ['quota' => $quota]));
                }
            }
        });
    }
}
