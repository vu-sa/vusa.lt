<?php

namespace App\Http\Requests\Meetings;

use App\Models\Document;
use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pick files straight out of SharePoint and file them under this meeting, so a protokolas can
 * be attached without first being registered in the documents area.
 */
class StoreMeetingSharepointDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can('update', $this->meeting())
            && $user->can('create', Document::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'documents' => 'required|array|max:20',
            'documents.*.name' => 'required|string',
            'documents.*.site_id' => 'required|string',
            'documents.*.list_id' => 'required|string',
            'documents.*.list_item_unique_id' => 'required|string',
        ];
    }

    public function meeting(): Meeting
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $meeting;
    }
}
