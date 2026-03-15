<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanningResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:file,url,text'],
            'content' => ['nullable', 'required_if:type,url', 'required_if:type,text', 'string', 'max:10000'],
            'file' => ['nullable', 'required_if:type,file', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:20480'],
            'academic_year_start' => ['required', 'integer', 'min:2020', 'max:2100'],
            'category' => ['nullable', 'string', 'in:tip_template,mvp_template'],
        ];
    }
}
