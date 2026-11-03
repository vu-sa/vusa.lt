<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use App\Models\Problem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateProblemRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Problem::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Opened from an institution's Problemos tab; an id the user cannot write to is ignored, not refused.
            'institution' => ['nullable', 'string', 'max:26'],
        ];
    }
}
