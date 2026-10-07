<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Documents\ResolvePickedDocuments;
use App\Actions\Documents\UpdateDocumentStatus;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Meetings\DestroyMeetingDocumentRequest;
use App\Http\Requests\Meetings\StoreMeetingDocumentRequest;
use App\Http\Requests\Meetings\StoreMeetingSharepointDocumentRequest;
use App\Models\Document;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Links SharePoint documents (nutarimai, protokolai) to the meeting that produced them.
 */
class MeetingDocumentController extends AdminController
{
    public function store(StoreMeetingDocumentRequest $request, Meeting $meeting): RedirectResponse
    {
        UpdateDocumentStatus::linkToMeeting($request->resolveDocument(), $meeting, $request->user());

        return back()->with('success', __('messages.meeting.document_linked'));
    }

    /**
     * Link files chosen in SharePoint's own picker, as the meeting page did before discovery.
     */
    public function storeFromSharepoint(StoreMeetingSharepointDocumentRequest $request, Meeting $meeting): RedirectResponse
    {
        $documents = ResolvePickedDocuments::execute($request->pickedItems(), $request->user());
        $outside = $documents->diff(Document::query()->linkableTo($meeting)->findMany($documents->modelKeys()));

        if ($outside->isNotEmpty()) {
            throw ValidationException::withMessages(['documents' => __('messages.meeting.picked_document_elsewhere', [
                'titles' => $outside->map(fn (Document $document): string => $document->title ?: $document->name)->join(', '),
            ])]);
        }

        $documents->each(fn (Document $document) => UpdateDocumentStatus::linkToMeeting($document, $meeting, $request->user()));

        return back()->with('success', __('messages.meeting.document_linked'));
    }

    /**
     * `Document $document` is what makes route-model binding substitute `{document}` — the
     * request needs the model, not the raw id, to check it belongs to this meeting.
     */
    public function destroy(DestroyMeetingDocumentRequest $request, Meeting $meeting, Document $document): RedirectResponse
    {
        $document = $request->document();
        $document->meeting_id = null;
        $document->save();

        return back()->with('success', __('messages.meeting.document_unlinked'));
    }
}
