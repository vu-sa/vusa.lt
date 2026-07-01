<?php

namespace App\Http\Requests\Comments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorization (author-only) is handled in the controller via the
 * CommentPolicy, so this request only validates input.
 */
class UpdateCommentRequest extends FormRequest
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
        ];
    }
}
