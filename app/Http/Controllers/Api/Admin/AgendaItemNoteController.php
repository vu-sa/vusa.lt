<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Pivots\AgendaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Persistence endpoints for the private collaborative agenda-item notes.
 *
 * Real-time syncing happens peer-to-peer over the Reverb presence channel
 * (agenda-item-notes.{id}); these endpoints only hydrate late joiners and store
 * a durable snapshot. Both are gated by the AgendaItem "update" ability.
 */
class AgendaItemNoteController extends ApiController
{
    /**
     * Return the persisted Y.js snapshot + HTML for an agenda item's notes.
     */
    public function show(AgendaItem $agendaItem): JsonResponse
    {
        $this->authorize('update', $agendaItem);

        $note = $agendaItem->note()->firstOrCreate([]);

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
            'yjs_state' => $note->yjs_state,
            'notes_html' => $note->notes_html,
            'updated_by' => $note->updated_by,
            'updated_at' => $note->updated_at?->toISOString(),
            'representatives' => $representatives,
        ]);
    }

    /**
     * Persist the debounced Y.js snapshot + rendered HTML.
     */
    public function update(Request $request, AgendaItem $agendaItem): JsonResponse
    {
        $this->authorize('update', $agendaItem);

        $validated = $request->validate([
            'yjs_state' => ['required', 'string'],
            'notes_html' => ['nullable', 'string'],
        ]);

        $note = $agendaItem->note()->updateOrCreate([], [
            'yjs_state' => $validated['yjs_state'],
            'notes_html' => $validated['notes_html'] ?? null,
            'updated_by' => $request->user()?->id,
        ]);

        return $this->jsonSuccess([
            'updated_by' => $note->updated_by,
            'updated_at' => $note->updated_at?->toISOString(),
        ], 'Pastabos išsaugotos.');
    }
}
