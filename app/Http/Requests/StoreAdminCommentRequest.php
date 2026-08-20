<?php

namespace App\Http\Requests;

use App\Enums\ModelEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * The commentable is resolved through CommentController::ALLOWED_COMMENTABLE_TYPES, so the
 * type here is a ModelEnum backing value rather than a class name.
 */
class StoreAdminCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'commentable_type' => ['required', new Enum(ModelEnum::class)],
            'commentable_id' => 'required',
            'comment' => 'required|string',
            'route' => 'nullable|string',
        ];
    }
}
