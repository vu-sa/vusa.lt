<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\UpdateAgendaItemNoteRequest;
use App\Models\AgendaItemNote;
use App\Models\Pivots\AgendaItem;
use Illuminate\Http\JsonResponse;

/**
 * Persistence endpoints for the private collaborative agenda-item notes.
 *
 * Real-time syncing happens peer-to-peer over the Reverb presence channel
 * (agenda-item-notes.{id}); these endpoints only hydrate late joiners and store
 * a durable snapshot. Both are gated by the AgendaItem "update" ability.
 */
class AgendaItemNoteApiController extends ApiController
{
    /**
     * Return the persisted Y.js snapshot + HTML for an agenda item's notes.
     */
    public function show(AgendaItem $agendaItem): JsonResponse
    {
        // Read is allowed for the broad `view` audience (coordinators / related
        // viewers) so they can see the persisted snapshot read-only. Editing
        // (update) and realtime presence remain gated on `update`.
        $this->authorize('view', $agendaItem);

        // Reading never creates the row: an empty row made the agenda list mark the
        // item as annotated. update() creates it on the first actual save instead.
        $note = $agendaItem->note;

        $agendaItem->loadMissing('meeting.institutions');

        $representatives = $agendaItem->meeting
            ?->getRepresentativesActiveAt()
            ->map(fn ($user) => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'profile_photo_path' => $user->profile_photo_path,
            ])
            ->values()
            ->all() ?? [];

        return $this->jsonSuccess([
            'yjs_state' => $note?->yjs_state,
            'notes_html' => $note?->notes_html,
            'updated_by' => $note?->updated_by,
            'updated_at' => $note?->updated_at?->toISOString(),
            'representatives' => $representatives,
        ]);
    }

    /**
     * Persist the debounced Y.js snapshot + rendered HTML.
     */
    public function update(UpdateAgendaItemNoteRequest $request, AgendaItem $agendaItem): JsonResponse
    {
        $validated = $request->validated();

        /** @var AgendaItemNote $note */
        $note = $agendaItem->note()->updateOrCreate([], [
            'yjs_state' => $validated['yjs_state'],
            'notes_html' => $validated['notes_html'] ?? null,
            'updated_by' => $request->user()?->id,
        ]);

        return $this->jsonSuccess([
            'updated_by' => $note->updated_by,
            'updated_at' => $note->updated_at?->toISOString(),
        ], __('messages.agenda_item.notes_saved'));
    }
}
