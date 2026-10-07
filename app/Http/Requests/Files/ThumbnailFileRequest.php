<?php

namespace App\Http\Requests\Files;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ThumbnailFileRequest extends FilePathRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [...parent::rules(), 'w' => 'nullable|integer'];
    }

    // An <img> request does not accept JSON, so Laravel would otherwise answer with a redirect.
    #[\Override]
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
