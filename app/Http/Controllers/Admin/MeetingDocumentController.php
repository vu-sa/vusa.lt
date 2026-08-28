<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\Meetings\DestroyMeetingDocumentRequest;
use App\Http\Requests\Meetings\StoreMeetingDocumentRequest;
use App\Http\Requests\Meetings\StoreMeetingSharepointDocumentRequest;
use App\Models\Document;
use App\Models\Meeting;
use App\Services\SharepointGraphService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

/**
 * Links SharePoint documents (nutarimai, protokolai) to the meeting that produced them.
 */
class MeetingDocumentController extends AdminController
{
    public function store(StoreMeetingDocumentRequest $request, Meeting $meeting): RedirectResponse
    {
        $document = $request->resolveDocument();
        $document->meeting_id = $meeting->id;
        $document->save();

        return back()->with('success', __('messages.meeting.document_linked'));
    }

    /**
     * Register SharePoint files as documents already filed under this meeting.
     *
     * Mirrors DocumentController::store(), except the models carry the meeting (and the
     * meeting's institution as a fallback) before SharePoint metadata is merged onto them.
     */
    public function storeFromSharepoint(StoreMeetingSharepointDocumentRequest $request, Meeting $meeting): RedirectResponse
    {
        $meeting->loadMissing('institutions');
        $fallbackInstitutionId = $meeting->institutions->first()?->id;

        /** @var Collection<int, Document> $documents */
        $documents = new Collection;

        foreach ($request->validated('documents') as $picked) {
            $document = new Document;
            $document->name = $picked['name'];
            $document->title = $picked['name'];
            $document->sharepoint_id = $picked['list_item_unique_id'];
            $document->sharepoint_site_id = $picked['site_id'];
            $document->sharepoint_list_id = $picked['list_id'];
            $document->meeting_id = $meeting->id;
            // SharePoint's own `Padalinys` field wins when it names one; this is the fallback
            // so a document filed from a meeting is never left without an institution.
            $document->institution_id = $fallbackInstitutionId;

            $documents->push($document);
        }

        if ($documents->isEmpty()) {
            return back()->with('info', __('messages.document.none_to_process'));
        }

        $graph = new SharepointGraphService(
            siteId: $documents->first()->sharepoint_site_id,
            driveId: config('filesystems.sharepoint.archive_drive_id'),
        );

        $processed = $graph->batchProcessDocuments($documents);

        // Already-registered files are filtered out by sharepoint_id, so adopt those here
        // rather than leaving the user with a silent no-op.
        $this->adoptAlreadyRegistered($meeting, $documents, $processed);

        return back()->with('success', __('messages.meeting.document_linked'));
    }

    /**
     * @param  Collection<int, Document>  $picked
     * @param  \Illuminate\Support\Collection<int, Document>|Collection<int, Document>  $processed
     */
    private function adoptAlreadyRegistered(Meeting $meeting, Collection $picked, $processed): void
    {
        $processedIds = $processed->pluck('sharepoint_id')->all();

        $skipped = $picked->pluck('sharepoint_id')->reject(fn ($id) => in_array($id, $processedIds, true));

        if ($skipped->isEmpty()) {
            return;
        }

        Document::query()
            ->whereIn('sharepoint_id', $skipped->all())
            ->whereNull('meeting_id')
            ->update(['meeting_id' => $meeting->id]);
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
