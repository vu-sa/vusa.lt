<?php

namespace App\Http\Requests\InstitutionLinks;

use App\Enums\InstitutionRelationKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Only the meaning and mutuality change; a different pair or direction is a new link.
 */
class UpdateInstitutionLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('institutionLink')) ?? false;
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(InstitutionRelationKind::class)],
            'mutual' => ['required', 'boolean'],
        ];
    }
}
