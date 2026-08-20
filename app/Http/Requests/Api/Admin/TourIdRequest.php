<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by markComplete() and resetTour(): the tour id is a client-supplied string such as
 * "spotlight-tenant-tab-v1". Both endpoints only ever touch the acting user's own progress.
 */
class TourIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tour_id' => 'required|string|max:100',
        ];
    }
}
