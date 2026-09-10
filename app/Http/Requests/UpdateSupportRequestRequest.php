<?php

namespace App\Http\Requests;

use App\Enums\SupportRequestVisibility;
use App\Models\SupportRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $supportRequest = $this->route('support_request') ?? $this->route('supportRequest');

        return $this->user()?->can('update', $supportRequest) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'support_request_type_id' => ['sometimes', 'integer', Rule::exists('support_request_types', 'id')->where('is_active', true)],
            'support_request_area_id' => ['sometimes', 'integer', Rule::exists('support_request_areas', 'id')->where('is_active', true)],
            'visibility' => ['required', Rule::enum(SupportRequestVisibility::class)],
            'roles' => ['required_if:visibility,roles', 'exclude_unless:visibility,roles', 'array', 'min:1'],
            'roles.*' => [
                'string',
                'distinct',
                Rule::exists('roles', 'id'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $user = $this->user();
                    if (! $user || $user->hasRole(config('permission.super_admin_role_name'))) {
                        return;
                    }

                    $supportRequest = $this->route('supportRequest') ?? $this->route('support_request');
                    if ($supportRequest instanceof SupportRequest && $supportRequest->roles()->where('roles.id', $value)->exists()) {
                        return;
                    }

                    $hasRole = $user->roles()->where('roles.id', $value)->exists()
                        || $user->current_duties()->whereHas('roles', fn ($q) => $q->where('roles.id', $value))->exists();

                    if (! $hasRole) {
                        $fail(__('Jūs negalite pasirinkti šios rolės.'));
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'context_url' => ['nullable', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'deleted_media_ids' => ['nullable', 'array'],
            'deleted_media_ids.*' => ['integer', Rule::exists('media', 'id')],
        ];
    }
}
