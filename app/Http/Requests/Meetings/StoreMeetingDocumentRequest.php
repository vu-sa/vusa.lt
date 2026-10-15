<?php

namespace App\Http\Requests\Meetings;

use App\Models\Document;
use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Link a nutarimas / protokolas to the meeting that produced it.
 */
class StoreMeetingDocumentRequest extends FormRequest
{
    private ?Document $document = null;

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
     * Moving a document between meetings is a deliberate unlink-then-link, never a side effect.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $linkedMeetingId = $this->resolveDocument()->meeting_id;

                if ($linkedMeetingId !== null && $linkedMeetingId !== $this->meeting()->id) {
                    $validator->errors()->add('document_id', __('messages.meeting.document_linked_elsewhere'));
                }
            },
        ];
    }

    /**
     * Resolved through the meeting's institutions and their padaliniai (Document::linkableTo).
     */
    public function resolveDocument(): Document
    {
        if ($this->document !== null) {
            return $this->document;
        }

        $document = Document::query()->linkableTo($this->meeting())->find($this->input('document_id'));

        abort_if($document === null, 403, 'Document does not belong to this meeting\'s institutions or tenants.');

        return $this->document = $document;
    }

    public function meeting(): Meeting
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $meeting;
    }
}
