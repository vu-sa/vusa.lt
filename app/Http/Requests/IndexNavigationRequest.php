<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use App\Http\Requests\Concerns\NormalizesBooleanFlags;
use App\Models\Navigation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexNavigationRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;
    use NormalizesBooleanFlags;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Navigation::class) ?? false;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanFlags('showDeleted');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'showDeleted' => ['nullable', 'boolean'],
            'lang' => ['nullable', 'string', 'max:5'],
        ];
    }
}
