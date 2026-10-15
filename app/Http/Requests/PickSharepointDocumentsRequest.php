<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Files chosen in SharePoint's file picker, by the ids it reports.
 */
class PickSharepointDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:20'],
            'documents.*.site_id' => ['required', 'string', 'max:255'],
            'documents.*.list_id' => ['required', 'string', 'max:255'],
            'documents.*.list_item_unique_id' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return list<array{site_id: string, list_id: string, list_item_unique_id: string}>
     */
    public function pickedItems(): array
    {
        return array_values($this->validated('documents'));
    }
}
