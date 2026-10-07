<?php

namespace App\Http\Requests;

use App\Models\File;
use App\Models\User;
use App\Services\FileStorageService;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreFilesRequest extends FormRequest
{
    public const int MAX_SIZE_KB = 51200;

    public function authorize(): bool
    {
        return $this->user()?->can('create', File::class) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array'],
            'files.*.file' => [
                'required',
                'file',
                'max:'.self::MAX_SIZE_KB,
                'mimes:'.implode(',', self::getAllowedExtensions()),
                'extensions:'.implode(',', self::getAllowedExtensions()),
            ],
            'path' => ['required', 'string'],
        ];
    }

    public function uploadDirectory(FileStorageService $storage, ModelAuthorizer $authorizer): string
    {
        $path = (string) $this->validated('path');
        $user = $this->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        // Validates every spelling, including the editor's `content/` marker it then discards.
        $directory = $storage->normalizeFilePath($path);

        if (FileStorageService::isTipTapPath($path)) {
            return $storage->resolveTipTapDirectory($user, $authorizer);
        }

        Gate::forUser($user)->authorize('createInDirectory', [File::class, $directory]);

        return $directory;
    }

    /** @return array<string, string> */
    #[\Override]
    public function messages(): array
    {
        return [
            'files.required' => trans('files.validation.files_required'),
            'files.array' => trans('files.validation.files_array'),
            'files.*.file.required' => trans('files.validation.file_required'),
            'files.*.file.file' => trans('files.validation.file_file'),
            'files.*.file.mimes' => trans('files.validation.file_mimes'),
            'files.*.file.extensions' => trans('files.validation.file_mimes'),
            'files.*.file.max' => trans('files.validation.file_max'),
            'path.required' => trans('files.validation.path_required'),
            'path.string' => trans('files.validation.path_string'),
        ];
    }

    /** @return array<string, string> */
    #[\Override]
    public function attributes(): array
    {
        return [
            'files' => 'failai',
            'files.*.file' => 'failas',
            'path' => 'kelias',
        ];
    }

    /** @return list<string> */
    public static function getAllowedExtensions(): array
    {
        return [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'zip', 'rar',
            'html', 'css', 'js', 'json', 'xml',
            'mp3', 'mp4', 'avi', 'mov', 'webm',
        ];
    }
}
