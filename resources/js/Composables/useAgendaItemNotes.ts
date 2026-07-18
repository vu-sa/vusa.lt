/**
 * Agenda-item flavor of the shared collaborative-document composable: wires the
 * private "Atstovų pastabos" document to its presence channel and API endpoints,
 * and exposes the meeting's representatives as the @mention pool.
 *
 * Created once per page so the doc survives the editor remounting when the user
 * toggles between the sidebar and the expanded dialog.
 */
import { ref } from 'vue';

import {
  useCollaborativeDocument,
} from '@/Composables/useCollaborativeDocument';
import type {
  CollaborativeDocUser,
  NotesMentionUser,
  NotesParticipant,
  NotesSaveStatus,
} from '@/Composables/useCollaborativeDocument';

export type { NotesMentionUser, NotesParticipant, NotesSaveStatus };

export function useAgendaItemNotes(agendaItemId: string, currentUser: CollaborativeDocUser) {
  const representatives = ref<NotesMentionUser[]>([]);

  const document = useCollaborativeDocument({
    channelName: `agenda-item-notes.${agendaItemId}`,
    showUrl: route('api.v1.admin.agendaItems.note.show', agendaItemId),
    persistUrl: route('api.v1.admin.agendaItems.note.update', agendaItemId),
    htmlField: 'notes_html',
    currentUser,
    onHydrated: (data) => {
      if (Array.isArray(data.representatives)) {
        representatives.value = data.representatives as NotesMentionUser[];
      }
    },
  });

  return {
    doc: document.doc,
    awareness: document.awareness,
    provider: document.provider,
    participants: document.participants,
    connectionState: document.connectionState,
    saveStatus: document.saveStatus,
    isHydrating: document.isHydrating,
    notesHtml: document.contentHtml,
    representatives,
    currentUserColor: document.currentUserColor,
    setHtml: document.setHtml,
    flush: document.flush,
    destroy: document.destroy,
  };
}
