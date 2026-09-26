<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Policies\DutyPolicy;
use App\Rules\SoftDeleteRules;
use App\Services\ModelAuthorizer;
use App\Support\MorphMap;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDutiableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $duty = $this->duty();
        $targetUser = $this->filled('user_id')
            ? User::query()->find($this->input('user_id'))
            : null;

        return $duty ? app(DutyPolicy::class)->managePeople($this->user(), $duty, $targetUser) : false;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $data = [
            'start_date' => $this->filled('start_date')
                ? Carbon::parse($this->input('start_date'))->format('Y-m-d')
                : now()->format('Y-m-d'),
        ];

        if ($this->filled('end_date')) {
            $data['end_date'] = Carbon::parse($this->input('end_date'))->format('Y-m-d');
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'duty_id' => ['required', 'ulid', SoftDeleteRules::existsLive('duties')],
            'user_id' => ['required', 'ulid', SoftDeleteRules::existsLive('users')],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'study_program_id' => ['nullable', 'ulid', SoftDeleteRules::existsLive('study_programs')],
            'study_program_note' => ['nullable', 'array'],
            'study_program_note.lt' => ['nullable', 'string', 'max:100'],
            'study_program_note.en' => ['nullable', 'string', 'max:100'],
            'additional_email' => ['nullable', 'email'],
            'additional_photo' => ['nullable', 'string'],
            'additional_photo_focal_point' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'array'],
            'description.lt' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'use_original_duty_name' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            if ($this->overlapsExistingTerm()) {
                $v->errors()->add('user_id', __('dutiables.assign.already_assigned'));

                return;
            }

            $this->validateTenantQuota($v);
        });
    }

    /**
     * The tenant a cross-tenant admin assigns the member *for*, or null when the
     * actor owns the duty (an ordinary seat). Delegated seats must carry the
     * tenant, otherwise the owning tenant's next duty save reads them as its own.
     */
    public function delegatedTenantId(): ?int
    {
        $duty = $this->duty();

        if ($duty === null || $this->user()->can('update', $duty)) {
            return null;
        }

        $duty->loadMissing('assignableTenants');

        $adminTenantIds = app(ModelAuthorizer::class)
            ->tenants($this->user(), 'duties.update.padalinys')->pluck('id');
        $memberTenantIds = User::query()->find($this->input('user_id'))?->tenants()->pluck('tenants.id') ?? collect();

        $tenantId = $duty->assignableTenants->pluck('id')
            ->intersect($adminTenantIds)
            ->intersect($memberTenantIds)
            ->first();

        return $tenantId === null ? null : (int) $tenantId;
    }

    private function duty(): ?Duty
    {
        return Duty::query()->find($this->input('duty_id'));
    }

    private function overlapsExistingTerm(): bool
    {
        $end = $this->input('end_date');

        return Dutiable::query()
            ->where('duty_id', $this->input('duty_id'))
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->where('dutiable_id', $this->input('user_id'))
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $this->input('start_date'));
            })
            ->when($end, fn ($query) => $query->where('start_date', '<=', $end))
            ->exists();
    }

    private function validateTenantQuota(Validator $validator): void
    {
        $tenantId = $this->delegatedTenantId();

        if ($tenantId === null) {
            return;
        }

        $quota = $this->duty()?->assignableTenants->firstWhere('id', $tenantId)?->getAttribute('pivot')?->quota;

        if ($quota === null) {
            return;
        }

        // Same "still counts" semantics as Duty::current_users().
        $occupied = Dutiable::query()
            ->where('duty_id', $this->input('duty_id'))
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->where('tenant_id', $tenantId)
            ->where(function ($query): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            })
            ->count();

        if ($occupied >= (int) $quota) {
            $validator->errors()->add('user_id', __('dutiables.assign.quota_exceeded', ['quota' => $quota]));
        }
    }
}
