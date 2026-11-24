<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Duty;
use App\Models\User;
use App\Rules\SoftDeleteRules;
use App\Rules\UniqueAmongTrashed;
use App\Services\DutyAssignmentService;
use App\Services\ModelAuthorizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

class BatchUpdateDutyUsersRequest extends FormRequest
{
    use ValidatesTenantScope;

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

            if (! $authorizer->allows($this->user(), 'users.create.padalinys')) {
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
        /** @var Duty $duty */
        $duty = $this->route('duty');
        $tenantRules = ['nullable', 'integer', Rule::in($duty->assignableTenants->modelKeys())];

        if (! $this->user()->can('update', $duty)) {
            $tenantRules[] = $this->tenantIdInAuthorizedScope('duties.update.padalinys');
        }

        return [
            'user_changes' => 'required|array',
            'tenant_id' => $tenantRules,
            'user_changes.*.user_id' => [
                'bail',
                'required',
                'string',
                'distinct',
                function ($attribute, $value, $fail): void {
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
            'user_changes.*.study_program_id' => ['nullable', 'string', SoftDeleteRules::existsLive('study_programs')],
            'new_users' => 'nullable|array',
            'new_users.*.name' => 'required|string|max:255',
            'new_users.*.email' => ['nullable', 'email', UniqueAmongTrashed::of('users', 'email')],
            'new_users.*.phone' => 'nullable|string|max:50',
            'new_users.*.temp_id' => 'required|string',
        ];
    }

    public function actingTenantId(): ?int
    {
        /** @var Duty $duty */
        $duty = $this->route('duty');

        if ($this->filled('tenant_id')) {
            return $this->integer('tenant_id');
        }

        if ($this->user()->can('update', $duty)) {
            return null;
        }

        $adminTenantIds = app(ModelAuthorizer::class)->tenants($this->user(), 'duties.update.padalinys')->pluck('id');
        $tenantId = $adminTenantIds->intersect($duty->assignableTenants->modelKeys())->first();

        return $tenantId === null ? null : (int) $tenantId;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes(
            'user_changes.*.end_date',
            'after_or_equal:user_changes.*.start_date',
            fn (Fluent $input, mixed $change) => $change instanceof Fluent
                && $change->get('action') === 'add' && filled($change->get('start_date')),
        );

        $validator->after(function (Validator $v): void {
            /** @var Duty $duty */
            $duty = $this->route('duty');

            if ($v->errors()->isNotEmpty() || $this->user()->can('update', $duty) || ($tenantId = $this->actingTenantId()) === null) {
                return;
            }

            $assignments = app(DutyAssignmentService::class);
            $quota = $assignments->tenantQuota($duty, $tenantId);

            if ($quota === null) {
                return;
            }

            $changes = collect($this->input('user_changes'));
            $today = today()->toDateString();
            $endsBeforeToday = fn (array $change): bool => isset($change['end_date']) && Carbon::parse($change['end_date'])->toDateString() < $today;
            $added = $changes->where('action', 'add')->reject($endsBeforeToday)->pluck('user_id')->all();
            $freed = $changes->where('action', 'remove')
                ->filter(fn (array $change) => ! isset($change['end_date']) || $endsBeforeToday($change)
                    || ! $assignments->manualTerms($duty->id, $change['user_id'], $tenantId)->notEnded()
                        ->whereDate('start_date', '<=', Carbon::parse($change['end_date'])->toDateString())->exists())
                ->pluck('user_id')->all();

            if ($added !== [] && $assignments->exceedsTenantQuota($duty, $tenantId, $quota, $added, $freed)) {
                $v->errors()->add('user_changes', __('dutiables.assign.quota_exceeded', ['quota' => $quota]));
            }
        });
    }
}
