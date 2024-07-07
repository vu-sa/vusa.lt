<?php

namespace App\Http\Requests;

use App\Models\Resource;

class StoreResourceRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Resource::class, $this->authorizer]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required|string',
            'description.lt' => 'required|string',
            'name.en' => 'nullable|string',
            'description.en' => 'nullable|string',
            'identifier' => 'nullable|string',
            'location' => 'required|string',
            'padalinys_id' => 'required|integer|exists:padaliniai,id',
            'capacity' => 'required|integer|min:1',
            'is_reservable' => 'required|boolean',
            'resource_category_id' => 'nullable|integer|exists:resource_categories,id',
            'media' => 'array|nullable',
            'media.*.file' => 'file|mimes:jpg,jpeg,png|max:10240',
        ];
    }
}
