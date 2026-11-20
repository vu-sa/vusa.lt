<?php

namespace App\Http\Requests;

use App\Enums\LocaleEnum;
use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditTenantMainPageRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

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
