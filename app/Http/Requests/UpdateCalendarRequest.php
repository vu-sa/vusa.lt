<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasImageValidation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarRequest extends FormRequest
{
    use HasImageValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->calendar);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    // NOTE: just transform date always to datetime, don't keep as number, as problems arise
    public function rules(): array
    {
        $rules = [
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
            'category' => 'nullable',
            'tenant_id' => 'required|integer',
        ];

        // Skip file validation during precognitive requests
        if (! $this->isPrecognitive()) {
            $rules['main_image'] = $this->singleImageRules(maxMB: 10);
            $rules['images'] = $this->imagesArrayRules(maxFiles: 20);
            $rules['images.*'] = $this->galleryImageRules(maxMB: 5);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return $this->imageValidationMessages();
    }
}
