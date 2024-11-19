<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->news);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'publish_time' => is_string(
                $this->input('publish_time')) ? 
                strtotime($this->input('publish_time')) :
                $this->input('publish_time') / 1000,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'lang' => 'required|string',
            'other_lang_id' => 'nullable|integer',
            'draft' => 'nullable|boolean',
            'short' => 'nullable',
            'image' => 'nullable|string',
            'image_author' => 'nullable|string',
            'publish_time' => 'required',
            'permalink' => 'required|string|unique:news,permalink,'.$this->news->id,
        ];
    }
}
