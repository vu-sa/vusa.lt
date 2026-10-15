<?php

namespace App\Http\Requests;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Publish or hide one or more SharePoint documents.
 */
class UpdateDocumentStatusRequest extends FormRequest
{
    /** @var Collection<int, Document>|null */
    private ?Collection $documents = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $this->documents()->isNotEmpty()
            && $this->documents()->every(fn (Document $document): bool => $user->can('publish', $document));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_ids' => ['required', 'array', 'min:1', 'max:100'],
            'document_ids.*' => ['integer', 'distinct'],
            'status' => ['required', Rule::in([DocumentStatus::Published->value, DocumentStatus::Hidden->value])],
        ];
    }

    public function status(): DocumentStatus
    {
        return DocumentStatus::from($this->validated('status'));
    }

    /**
     * Missing ids fail authorization, so a crafted list cannot probe other documents.
     *
     * @return Collection<int, Document>
     */
    public function documents(): Collection
    {
        if ($this->documents !== null) {
            return $this->documents;
        }

        $ids = collect((array) $this->input('document_ids'))->filter(fn ($id) => is_numeric($id))->map(intval(...))->unique();
        $documents = Document::query()->with('institution')->findMany($ids);

        return $this->documents = $documents->count() === $ids->count() ? $documents : new Collection;
    }
}
