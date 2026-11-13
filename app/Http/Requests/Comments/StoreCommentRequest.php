<?php

namespace App\Http\Requests\Comments;

use App\Enums\CommentKind;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Authorization is handled in the controller against the parent commentable
 * (Gate::authorize('view', $commentable)), so this request only validates input.
 */
class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'parent_id' => ['nullable', 'string', Rule::exists('comments', 'id')],
            'kind' => ['nullable', Rule::in([CommentKind::Comment->value, CommentKind::Poll->value, CommentKind::Topic->value])],
            'title' => ['required_if:kind,topic', 'prohibited_unless:kind,topic', 'string', 'max:160'],
            'dutiable_id' => ['nullable', 'string'],
            'image_ids' => ['nullable', 'array', 'max:4'],
            'image_ids.*' => ['integer', 'distinct'],
            'metadata' => ['nullable', 'array'],
            // Option ids are assigned on the server.
            'metadata.poll' => ['array', 'required_if:kind,poll'],
            'metadata.poll.options' => ['array', 'min:2', 'max:10', 'required_if:kind,poll'],
            'metadata.poll.options.*.label' => ['required', 'string', 'max:255'],
            'metadata.poll.allow_multiple' => ['nullable', 'boolean'],
            'metadata.poll.closes_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if (in_array($this->input('kind'), [CommentKind::Poll->value, CommentKind::Topic->value], true) && $this->filled('parent_id')) {
                $validator->errors()->add('parent_id', __('validation.prohibited', ['attribute' => 'parent_id']));
            }
        });
    }
}
