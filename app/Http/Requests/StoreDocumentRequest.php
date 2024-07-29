<?php

namespace App\Http\Requests;

use App\Models\Document;

class StoreDocumentRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Document::class, $this->authorizer]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'documents' => 'required|array',
            'documents.*.name' => 'required|string',
            'documents.*.site_id' => 'required|string',
            'documents.*.list_id' => 'required|string',
            'documents.*.list_item_unique_id' => 'required|string|unique:documents,sharepoint_id',
        ];
    }
}
