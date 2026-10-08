<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\PendingUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Anyone signed in may stage an image: it stays theirs and invisible until a form they are
 * authorized to save attaches it.
 */
class StorePendingUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'dimensions:max_width=12000,max_height=12000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (PendingUpload::query()->whereBelongsTo($this->user())->count() >= PendingUpload::MAX_PER_USER) {
                    $validator->errors()->add('image', __('files.pending_upload_limit'));
                }
            },
        ];
    }
}
