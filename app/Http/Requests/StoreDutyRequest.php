<?php

namespace App\Http\Requests;

use App\Models\Duty;
use Illuminate\Foundation\Http\FormRequest;

class StoreDutyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Duty::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required',
            'name.en' => 'nullable',
            'description.lt' => 'nullable',
            'description.en' => 'nullable',
            'email' => 'nullable|email',
            'institution_id' => 'required',
            'places_to_occupy' => 'nullable|integer',
            'contacts_grouping' => 'required|in:none,study_program,tenant',
            'types' => 'nullable|array',
            'roles' => 'nullable|array',
            'current_users' => 'nullable|array',
        ];
    }
}
