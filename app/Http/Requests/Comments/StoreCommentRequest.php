<?php

namespace App\Http\Requests\Comments;

use App\Enums\CommentKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'parent_id' => ['nullable', 'string', 'exists:comments,id'],
            'kind' => ['nullable', Rule::in([CommentKind::Comment->value, CommentKind::Poll->value])],
            'metadata' => ['nullable', 'array'],
            // Poll definition: clients send option labels only — ids are assigned
            // server-side. closes_at and allow_multiple are optional.
            'metadata.poll' => ['array', 'required_if:kind,poll'],
            'metadata.poll.options' => ['array', 'min:2', 'max:10', 'required_if:kind,poll'],
            'metadata.poll.options.*.label' => ['required', 'string', 'max:255'],
            'metadata.poll.allow_multiple' => ['nullable', 'boolean'],
            'metadata.poll.closes_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // A poll is always a thread root — it can't be posted as a reply.
            if ($this->input('kind') === CommentKind::Poll->value && $this->filled('parent_id')) {
                $validator->errors()->add('parent_id', __('A poll cannot be a reply.'));
            }
        });
    }
}
