<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BatchUpdateDutyUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Duty $duty */
        $duty = $this->route('duty');

        // Owning-tenant admin or cross-tenant assignable-tenant admin.
        if (! $this->user()->can('managePeople', $duty)) {
            return false;
        }

        // If creating new users, check users.create.padalinys permission.
        if (! empty($this->input('new_users', []))) {
            $authorizer = app(ModelAuthorizer::class);

            if (! $authorizer->forUser($this->user())->checkAllRoleables('users.create.padalinys')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_changes' => 'required|array',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'user_changes.*.user_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (str_starts_with($value, 'new-')) {
                        return;
                    }

                    if (! User::where('id', $value)->exists()) {
                        $fail(__('validation.exists', ['attribute' => 'user_id']));
                    }
                },
            ],
            'user_changes.*.action' => 'required|in:add,remove',
            'user_changes.*.start_date' => 'nullable|date',
            'user_changes.*.end_date' => 'nullable|date',
            'user_changes.*.study_program_id' => 'nullable|string|exists:study_programs,id',
            'new_users' => 'nullable|array',
            'new_users.*.name' => 'required|string|max:255',
            'new_users.*.email' => 'required|email|unique:users,email',
            'new_users.*.phone' => 'nullable|string|max:50',
            'new_users.*.temp_id' => 'required|string',
            'places_to_occupy' => 'nullable|integer|min:1',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var Duty $duty */
            $duty = $this->route('duty');
            $isOwningAdmin = $this->user()->can('update', $duty);

            if ($isOwningAdmin) {
                return;
            }

            $duty->loadMissing('assignableTenants');
            $authorizer = app(ModelAuthorizer::class)->forUser($this->user());
            $adminTenantIds = $authorizer->getTenants('duties.update.padalinys')->pluck('id');

            foreach ($duty->assignableTenants as $tenant) {
                if (! $adminTenantIds->contains($tenant->id) || $tenant->pivot->quota === null) {
                    continue;
                }

                $addCount = collect($this->input('user_changes', []))
                    ->where('action', 'add')
                    ->count();

                if ($addCount === 0) {
                    continue;
                }

                // Count by tenant_id column — explicit and accurate.
                $currentCount = Dutiable::where('duty_id', $duty->id)
                    ->where('dutiable_type', User::class)
                    ->where('tenant_id', $tenant->id)
                    ->active()
                    ->count();

                if (($currentCount + $addCount) > $tenant->pivot->quota) {
                    $v->errors()->add('user_changes', "Padalinio kvota ({$tenant->pivot->quota}) viršyta.");
                }
            }
        });
    }
}
