<?php

namespace App\Http\Requests\MailingLists;

use App\Enums\MailingListAddressMode;
use App\Enums\MailingListSourceType;
use App\Enums\MailingListTenantScope;
use App\Enums\TenantType;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\MailingList;
use App\Models\Tenant;
use App\Rules\SoftDeleteRules;
use App\Services\ModelAuthorizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMailingListSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('mailingList'));
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = (is_string($this->input('type')) ? MailingListSourceType::tryFrom($this->input('type')) : null);

        return [
            'type' => ['required', Rule::enum(MailingListSourceType::class)],
            'source_id' => $this->sourceIdRules($type),
            'email' => [Rule::requiredIf($type === MailingListSourceType::Email), 'nullable', 'email', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:today'],
            'address_mode' => ['required', Rule::enum(MailingListAddressMode::class)],
            'filters' => ['nullable', 'array'],
            'filters.tenant_scope' => ['nullable', Rule::enum(MailingListTenantScope::class)],
            'filters.tenant_ids' => ['nullable', 'array', 'required_if:filters.tenant_scope,selected'],
            'filters.tenant_ids.*' => ['integer', 'exists:tenants,id'],
            'filters.include_subtypes' => ['nullable', 'boolean'],
            'filters.exclude_institution_type_ids' => ['nullable', 'array'],
            'filters.exclude_institution_type_ids.*' => ['integer', SoftDeleteRules::existsLive('institution_types')],
            'filters.duty_type_ids' => ['nullable', 'array'],
            'filters.duty_type_ids.*' => ['integer', SoftDeleteRules::existsLive('duty_types')],
        ];
    }

    /** @return list<int>|null */
    public function authorizedTenantIds(): ?array
    {
        $scope = app(ModelAuthorizer::class)->scope($this->user(), 'mailingLists.update.padalinys');

        return $scope->isAllScope ? null : $scope->tenantIds()->all();
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $tenants = $this->authorizedTenantIds();
            if ($tenants === null) {
                return;
            }

            $type = MailingListSourceType::from($this->input('type'));
            $id = $this->input('source_id');
            $allowed = match ($type) {
                MailingListSourceType::Duty => Duty::query()->whereKey($id)->whereHas('institution', fn ($query) => $query->whereIn('tenant_id', $tenants))->exists(),
                MailingListSourceType::Institution => Institution::query()->whereKey($id)->whereIn('tenant_id', $tenants)->exists(),
                MailingListSourceType::MailingList => MailingList::query()->whereKey($id)->whereIn('tenant_id', $tenants)->exists(),
                default => true,
            };

            if (! $allowed) {
                $validator->errors()->add('source_id', __('mailing_lists.messages.source_forbidden'));
            }

            $tenantType = match ($this->input('filters.tenant_scope')) {
                'main' => TenantType::Pagrindinis,
                'padaliniai' => TenantType::Padalinys,
                default => null,
            };
            if ($tenantType !== null && in_array($type, [MailingListSourceType::DutyType, MailingListSourceType::InstitutionType], true)
                && ! Tenant::query()->whereIn('id', $tenants)->where('type', $tenantType)->exists()) {
                $validator->errors()->add('filters.tenant_scope', __('mailing_lists.messages.source_forbidden'));
            }

            if (array_diff((array) $this->input('filters.tenant_ids', []), $tenants) !== []) {
                $validator->errors()->add('filters.tenant_ids', __('mailing_lists.messages.source_forbidden'));
            }
        }];
    }

    /**
     * @return array<int, mixed>
     */
    private function sourceIdRules(?MailingListSourceType $type): array
    {
        return match ($type) {
            MailingListSourceType::Duty => ['required', 'string', SoftDeleteRules::existsLive('duties')],
            MailingListSourceType::Institution => ['required', 'string', SoftDeleteRules::existsLive('institutions')],
            MailingListSourceType::DutyType => ['required', 'integer', SoftDeleteRules::existsLive('duty_types')],
            MailingListSourceType::InstitutionType => ['required', 'integer', SoftDeleteRules::existsLive('institution_types')],
            MailingListSourceType::MailingList => [
                'required', 'string',
                Rule::exists('mailing_lists', 'id'),
                Rule::notIn([$this->route('mailingList')->id]),
            ],
            default => ['nullable', 'prohibited'],
        };
    }
}
