<?php

namespace App\Http\Requests;

use App\Enums\LocaleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditTenantMainPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateMainPage', $this->route('tenant')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['nullable', Rule::enum(LocaleEnum::class)],
        ];
    }

    public function requestedLocale(): LocaleEnum
    {
        return LocaleEnum::tryFrom((string) $this->validated('locale')) ?? LocaleEnum::LT;
    }
}
