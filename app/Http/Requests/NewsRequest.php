<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasImageValidation;
use App\Http\Requests\Concerns\ValidatesContentParts;
use App\Models\News;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class NewsRequest extends FormRequest
{
    use HasImageValidation;
    use ValidatesContentParts;

    protected function prepareForValidation(): void
    {
        $value = $this->input('publish_time');
        if ($value === null || $value === '') {
            return;
        }
        $timestamp = is_numeric($value)
            ? (abs((float) $value) > 100_000_000_000 ? (float) $value / 1000 : (float) $value)
            : (is_string($value) ? strtotime($value) : false);
        if ($timestamp !== false) {
            $this->merge(['publish_time' => Carbon::createFromTimestamp($timestamp, config('app.timezone'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->contentPartRules(),
            'content_version' => ['nullable', 'string', 'size:64'],
            'pairing_confirmation' => ['nullable', 'string', 'size:64'],
            'content.parts.*.key' => ['nullable', 'string', 'max:100'],
            'title' => 'required|string|max:255',
            'lang' => 'required|in:lt,en',
            'other_lang_id' => ['nullable', 'integer', SoftDeleteRules::existsLive('news')],
            'draft' => 'nullable|boolean',
            'publish_time' => 'required_unless:draft,true|nullable|date',
            'show_breadcrumbs' => ['boolean'],
            'highlights' => 'nullable|array|max:3',
            'highlights.*' => 'nullable|string|max:500',
            'content' => 'required|array',
            'tags' => 'nullable|array',
            'tags.*' => ['integer', SoftDeleteRules::existsLive('tags')],
            ...$this->imageMediaRules('image_media', $this->route('news') instanceof News ? $this->route('news') : new News, 'image'),
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'content.required' => trans('forms.validation.content.required'),
            ...$this->contentPartMessages(),
        ];
    }
}
