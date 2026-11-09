<?php

namespace App\Http\Requests;

use App\Models\LearningStep;
use Illuminate\Foundation\Http\FormRequest;

class SyncLearningStepGlossaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $step = $this->route('learningStep');

        return $step instanceof LearningStep && $this->user()->can('update', $step);
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
