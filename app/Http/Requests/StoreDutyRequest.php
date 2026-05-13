<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Models\Institution;
use App\Services\ModelAuthorizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDutyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Duty::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required',
            'name.en' => 'nullable',
            'description.lt' => 'nullable',
            'description.en' => 'nullable',
            'email' => 'nullable|email',
            'institution_id' => 'required',
            'places_to_occupy' => 'nullable|integer',
            'contacts_grouping' => 'required|in:none,study_program,tenant',
            'types' => 'nullable|array',
            'roles' => 'nullable|array',
            'current_users' => 'nullable|array',
            'ex_officio_target_duty_ids' => 'nullable|array',
            'ex_officio_target_duty_ids.*' => 'ulid|distinct|exists:duties,id',
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
            $authorizer = app(ModelAuthorizer::class)->forUser($this->user());
            $hasGlobalDutyScope = $authorizer->check('duties.create.*');

            $sourceTenantId = Institution::whereKey($this->input('institution_id'))->value('tenant_id');

            // The institution must belong to a tenant the user may create duties in
            // (unless global scope) — otherwise an admin could create a duty inside
            // another tenant's institution.
            if (! $hasGlobalDutyScope) {
                $allowedCreateTenantIds = $authorizer->getTenants('duties.create.padalinys')->pluck('id')->all();
                if (! in_array((int) $sourceTenantId, $allowedCreateTenantIds, true)) {
                    $v->errors()->add('institution_id', __('Negalite kurti pareigybės šiame padalinyje.'));
                }
            }

            // Ex-officio targets must belong to the same tenant as the new duty,
            // unless the user has the global duties scope.
            if (! $hasGlobalDutyScope) {
                $targetIds = array_filter((array) $this->input('ex_officio_target_duty_ids', []));

                if ($targetIds) {
                    $foreign = Duty::whereIn('id', $targetIds)
                        ->whereHas('institution', fn ($q) => $q->where('tenant_id', '!=', $sourceTenantId))
                        ->exists();

                    if ($foreign) {
                        $v->errors()->add('ex_officio_target_duty_ids', __('Ex-officio pareigos turi priklausyti tam pačiam padaliniui.'));
                    }
                }
            }

            // Assignable tenants must be ones the user can manage (unless global scope).
            if (! $hasGlobalDutyScope) {
                $allowedTenantIds = $authorizer->getTenants('duties.create.padalinys')->pluck('id')->all();
                foreach ((array) $this->input('assignable_tenants', []) as $i => $row) {
                    if (isset($row['tenant_id']) && ! in_array((int) $row['tenant_id'], $allowedTenantIds, true)) {
                        $v->errors()->add("assignable_tenants.$i.tenant_id", __('Negalite priskirti šio padalinio.'));
                    }
                }
            }
        });
    }
}
