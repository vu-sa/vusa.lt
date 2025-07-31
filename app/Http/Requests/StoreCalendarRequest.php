<?php

namespace App\Http\Requests;

use App\Models\Calendar;
use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Calendar::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title.lt' => 'required|string',
            'title.en' => 'nullable|string',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'location.lt' => 'nullable|string',
            'location.en' => 'nullable|string',
            'organizer.lt' => 'nullable|string',
            'organizer.en' => 'nullable|string',
            'cto_url.lt' => 'nullable|url',
            'cto_url.en' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'video_url' => 'nullable',
            'is_draft' => 'boolean',
            'is_all_day' => 'boolean',
            'is_international' => 'boolean',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after:date',
            'category_id' => 'nullable|exists:categories,id',
            'tenant_id' => 'required|integer',
            'images' => 'nullable|array',
            'images.*.file' => 'image|max:5120|mimes:jpeg,jpg,png', // Max 5MB per image
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'images.*.file.max' => 'Paveiksliukas negali būti didesnis nei 5MB. Sumažinkite failo dydį.',
            'images.*.file.image' => 'Failas turi būti paveiksliukas (JPEG, PNG).',
        ];
    }
}
