<?php

namespace App\Http\Requests\Meetings;

use App\Models\Document;
use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Link a nutarimas / protokolas to the meeting that produced it.
 */
class StoreMeetingDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can('update', $this->meeting())
            && $user->can('update', $this->resolveDocument());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_id' => ['required', 'integer'],
        ];
    }

    /**
     * Resolved through the meeting's own institutions, never straight off the id: `find()` on
     * Document would happily hand back another body's paperwork.
     */
    public function resolveDocument(): Document
    {
        $document = Document::query()
            ->whereIn('institution_id', $this->meeting()->institutions()->pluck('institutions.id'))
            ->find($this->input('document_id'));

        abort_if($document === null, 403, 'Document does not belong to this meeting\'s institutions.');

        return $document;
    }

    public function meeting(): Meeting
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $meeting;
    }
}
