<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleCommentReactionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'emoji' => ['required', 'string', Rule::in(Comment::ALLOWED_REACTIONS)],
        ];
    }
}
