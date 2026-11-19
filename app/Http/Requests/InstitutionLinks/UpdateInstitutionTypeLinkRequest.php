<?php

namespace App\Http\Requests\InstitutionLinks;

use App\Enums\InstitutionRelationKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Only the meaning and mutuality change; a different pair, direction or reach is a new link.
 */
class UpdateInstitutionTypeLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(InstitutionRelationKind::class)],
            'mutual' => ['required', 'boolean'],
        ];
    }
}
