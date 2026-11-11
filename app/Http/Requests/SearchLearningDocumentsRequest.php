<?php

namespace App\Http\Requests;

use App\Models\KnowledgeItem;
use Illuminate\Foundation\Http\FormRequest;

class SearchLearningDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', KnowledgeItem::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }
}
