<?php

namespace App\Http\Requests;

use App\Models\StudySet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudySetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', StudySet::class);
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
            'name.en' => 'nullable|string|max:255',
            'description.lt' => 'nullable|string|max:2000',
            'description.en' => 'nullable|string|max:2000',
            'order' => 'required|integer|min:0',
            'is_visible' => 'boolean',
            'tenant_id' => 'required|exists:tenants,id',
            'courses' => 'nullable|array',
            'courses.*.name.lt' => 'required|string|max:255',
            'courses.*.name.en' => 'nullable|string|max:255',
            'courses.*.semester' => 'required|string|in:autumn,spring',
            'courses.*.credits' => 'required|integer|min:1',
            'courses.*.order' => 'required|integer|min:0',
            'courses.*.is_visible' => 'boolean',
            'reviews' => 'nullable|array',
            'reviews.*.lecturer.lt' => 'required|string|max:255',
            'reviews.*.lecturer.en' => 'nullable|string|max:255',
            'reviews.*.comment.lt' => 'required|string|max:5000',
            'reviews.*.comment.en' => 'nullable|string|max:5000',
            'reviews.*.study_set_course_id' => 'required|string',
            'reviews.*.is_visible' => 'boolean',
        ];
    }
}
