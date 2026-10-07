<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Delete documents whose files are gone from SharePoint.
 */
class DestroyRemovedDocumentsRequest extends FormRequest
{
    /** @var Collection<int, Document>|null */
    private ?Collection $documents = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $this->documents()->isNotEmpty()
            && $this->documents()->every(fn (Document $document): bool => $user->can('delete', $document));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_ids' => ['required', 'array', 'min:1', 'max:100'],
            'document_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->documents()->contains(fn (Document $document): bool => $document->removed_from_sharepoint_at === null)) {
                    $validator->errors()->add('document_ids', __('messages.document.only_removed_deletable'));
                }
            },
        ];
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
