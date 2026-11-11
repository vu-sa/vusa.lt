<?php

namespace App\Http\Requests;

use App\Models\GlossaryTerm;
use Illuminate\Foundation\Http\FormRequest;

class GlossaryTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        $term = $this->route('glossaryTerm');

        return $this->user()->can($term ? 'update' : 'create', $term ?? GlossaryTerm::class);
    }

    public function rules(): array
    {
        return [
            'term' => ['required', 'array:lt,en'], 'term.lt' => ['required', 'string', 'max:255'], 'term.en' => ['nullable', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'array:lt,en'], 'abbreviation.*' => ['nullable', 'string', 'max:32'],
            'definition' => ['required', 'array:lt,en'], 'definition.lt' => ['required', 'string', 'max:20000'], 'definition.en' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
