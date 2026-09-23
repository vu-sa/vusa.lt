<?php

namespace App\Http\Requests\Content;

/**
 * Publish or unpublish records; `published` is mapped to each model's own column.
 */
abstract class BulkContentStatusRequest extends BulkContentRequest
{
    protected function ability(): string
    {
        return 'update';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'published' => ['required', 'boolean'],
        ];
    }
}
