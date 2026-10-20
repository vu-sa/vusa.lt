<?php

namespace App\Http\Requests;

use App\Enums\ResponsibilityPurpose;
use App\Enums\SystemDutyType;
use App\Rules\SoftDeleteRules;
use App\Settings\SettingsSettings;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Moving a purpose changes who gets tasks, registrations and notifications, so it is a settings
 * change, not a responsibility edit.
 */
class UpdateSystemPurposesSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'purposes' => ['present', 'array', $this->onePurposeEach(...)],
            'duty_types' => ['present', 'array'],
        ];

        foreach (ResponsibilityPurpose::cases() as $purpose) {
            $rules["purposes.{$purpose->value}"] = [
                'nullable', 'string',
                Rule::exists('roles', 'id')->whereNot('name', config('permission.super_admin_role_name')),
            ];
        }

        foreach (SystemDutyType::cases() as $part) {
            $rules["duty_types.{$part->value}"] = ['nullable', 'integer', SoftDeleteRules::existsLive('duty_types')];
        }

        $rules['duty_types.'.SystemDutyType::Mentor->value][] = $this->differsFromCurator(...);

        return $rules;
    }

    /** A responsibility carries at most one purpose. */
    private function onePurposeEach(string $attribute, mixed $value, Closure $fail): void
    {
        $chosen = array_filter((array) $value);

        if (count($chosen) !== count(array_unique($chosen))) {
            $fail(__('settings.purposes.one_each'));
        }
    }

    private function differsFromCurator(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && (string) $value === (string) $this->input('duty_types.'.SystemDutyType::Curator->value)) {
            $fail(__('settings.purposes.curator_not_mentor'));
        }
    }
}
