<?php

namespace App\Http\Requests\Meetings;

use App\Http\Requests\PickSharepointDocumentsRequest;
use App\Models\Meeting;

/**
 * Pick files in SharePoint and link them to this meeting, publishing the ones nobody decided on yet.
 */
class StoreMeetingSharepointDocumentRequest extends PickSharepointDocumentsRequest
{
    #[\Override]
    public function authorize(): bool
    {
        return parent::authorize() && $this->user()->can('update', $this->meeting());
    }

    public function meeting(): Meeting
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $meeting;
    }
}
