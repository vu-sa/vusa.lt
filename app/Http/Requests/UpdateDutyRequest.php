<?php

namespace App\Http\Requests;

use App\Models\Duty;
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
            'ex_officio_target_duty_ids.*' => ['ulid', 'distinct', 'exists:duties,id', 'not_in:'.$duty->id],
            'assignable_tenants' => 'nullable|array',
            'assignable_tenants.*.tenant_id' => 'required|integer|exists:tenants,id',
            'assignable_tenants.*.quota' => 'nullable|integer|min:1',
            'assignable_tenants.*.user_ids' => 'nullable|array',
            'assignable_tenants.*.user_ids.*' => 'string|exists:users,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
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

            // Enforce per-tenant quota against the requested user_ids count.
            foreach ((array) $this->input('assignable_tenants', []) as $i => $row) {
                $quota = $row['quota'] ?? null;
                $userCount = count(array_unique((array) ($row['user_ids'] ?? [])));
                if ($quota !== null && $userCount > (int) $quota) {
                    $v->errors()->add("assignable_tenants.$i.user_ids", __('Padalinio kvota (:quota) viršyta.', ['quota' => $quota]));
                }
            }
        });
    }
}
