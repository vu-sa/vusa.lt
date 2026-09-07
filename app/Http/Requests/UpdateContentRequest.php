<?php

namespace App\Http\Requests;

use App\Enums\LocaleEnum;
use App\Http\Requests\Concerns\ValidatesContentParts;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateContentRequest extends FormRequest
{
    use ValidatesContentParts;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('updateMainPage', $this->tenant);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', new Enum(LocaleEnum::class)],
            ...$this->contentPartRules('parts'),
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return $this->contentPartMessages('parts');
    }
}
