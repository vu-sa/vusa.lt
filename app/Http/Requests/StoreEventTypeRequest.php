<?php

namespace App\Http\Requests;

use App\Models\EventType;
use App\Rules\UniqueAmongTrashed;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', EventType::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', UniqueAmongTrashed::of('event_types', 'slug')],
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
