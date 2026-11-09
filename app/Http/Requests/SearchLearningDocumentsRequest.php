<?php

namespace App\Http\Requests;

use App\Models\LearningStep;
use Illuminate\Foundation\Http\FormRequest;

class SearchLearningDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LearningStep::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }
}
