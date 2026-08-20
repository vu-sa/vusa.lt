<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleCommentReactionRequest extends FormRequest
{
    /**
     * Kept here rather than in the controller so the ability is still checked before the
     * payload is looked at, as it was when validation was inline.
     */
    public function authorize(): bool
    {
        return $this->user()->can('react', $this->route('comment'));
    }

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
