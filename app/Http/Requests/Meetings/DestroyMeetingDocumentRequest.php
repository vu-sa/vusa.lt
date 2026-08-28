<?php

namespace App\Http\Requests\Meetings;

use App\Models\Document;
use App\Models\Meeting;
use Illuminate\Foundation\Http\FormRequest;

class DestroyMeetingDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can('update', $this->meeting())
            && $user->can('update', $this->document());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Only a document already linked to *this* meeting may be unlinked through it.
     */
    public function document(): Document
    {
        /** @var Document $document */
        $document = $this->route('document');

        abort_if($document->meeting_id !== $this->meeting()->id, 403, 'Document is not linked to this meeting.');

        return $document;
    }

    public function meeting(): Meeting
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $meeting;
    }
}
