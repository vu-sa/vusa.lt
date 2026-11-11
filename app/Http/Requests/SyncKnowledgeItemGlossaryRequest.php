<?php

namespace App\Http\Requests;

use App\Models\KnowledgeItem;
use Illuminate\Foundation\Http\FormRequest;

class SyncKnowledgeItemGlossaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $step = $this->route('knowledgeItem');

        return $step instanceof KnowledgeItem && $this->user()->can('update', $step);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'term_ids' => ['nullable', 'array'],
            'term_ids.*' => ['required', 'string', 'exists:glossary_terms,id'],
        ];
    }
}
