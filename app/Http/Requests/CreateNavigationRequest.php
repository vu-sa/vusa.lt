<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use App\Models\Navigation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateNavigationRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Navigation::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:16'],
            'lang' => ['nullable', 'string', 'max:5'],
        ];
    }
}
