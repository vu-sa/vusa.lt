<?php

namespace App\Http\Requests;

class UpdateNewsRequest extends NewsRequest
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
        $publishTime = $this->input('publish_time');

        if ($publishTime !== null) {
            $this->merge([
                'publish_time' => is_string($publishTime)
                    ? strtotime($publishTime)
                    : $publishTime / 1000,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'permalink' => 'required|string|unique:news,permalink,'.$this->news->id,
            'image' => 'nullable|string',
            'short' => 'nullable',
            'lang' => 'required|string',
        ]);
    }
}
