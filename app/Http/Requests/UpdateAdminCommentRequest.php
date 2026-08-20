<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Editing a comment only ever changes its body; the commentable it hangs off is fixed at
 * creation, so it is deliberately not accepted here.
 */
class UpdateAdminCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller authorizes against the comment resolved from the route.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => 'required|string',
        ];
    }
}
