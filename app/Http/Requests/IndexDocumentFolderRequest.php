<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The managers' document view: one folder of the SharePoint document system, or (`flat`) every file below it.
 */
class IndexDocumentFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Absent: open the folder holding the user's files. Empty: the top.
            'path' => ['nullable', 'string', 'max:1000'],
            'show' => ['nullable', 'string', 'in:all,published,pending,hidden,removed'],
            // Every file below the folder, newest first, instead of its subfolders and own files.
            'flat' => ['nullable', 'boolean'],
            'content_type' => ['nullable', 'string', 'max:125'],
            'search' => ['nullable', 'string', 'max:200'],
            // "Rodyti daugiau": files already shown.
            'offset' => ['nullable', 'integer', 'min:0'],
            // A refresh asks for everything already shown at once.
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function hasPath(): bool
    {
        return $this->has('path');
    }

    public function folderPath(): string
    {
        return trim((string) $this->validated('path'), '/');
    }

    /**
     * @return 'all'|'published'|'pending'|'hidden'|'removed'
     */
    public function show(): string
    {
        return $this->validated('show') ?? 'all';
    }

    /**
     * Files removed from SharePoint no longer have a folder worth browsing.
     */
    public function flat(): bool
    {
        return $this->boolean('flat') || $this->show() === 'removed';
    }

    public function limit(int $default): int
    {
        return (int) ($this->validated('limit') ?? $default);
    }

    public function offset(): int
    {
        return (int) ($this->validated('offset') ?? 0);
    }

    public function contentType(): ?string
    {
        return $this->validated('content_type');
    }
}
