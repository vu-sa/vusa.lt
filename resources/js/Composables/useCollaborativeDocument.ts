/**
 * Owns the lifecycle of one real-time collaborative Y.js document synced over a
 * Reverb presence channel. Shared by agenda-item notes and workspace documents.
 *
 * Responsibilities:
 *  - hold the single `Y.Doc` + awareness shared by any mounted editor(s),
 *  - connect it to the presence channel via {@link ReverbYjsProvider},
 *  - hydrate the durable snapshot and persist debounced snapshots through the
 *    caller-provided endpoints,
 *  - expose the live participant list and a save-status indicator.
 *
 * Create once per page so the doc survives editor remounts (e.g. toggling
 * between a sidebar and an expanded dialog).
 */
import { onScopeDispose, ref, shallowRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Awareness } from 'y-protocols/awareness';
import * as Y from 'yjs';

import { ReverbYjsProvider } from '@/Composables/useYjsReverbProvider';

export type NotesSaveStatus = 'idle' | 'saving' | 'saved' | 'dirty';

export interface NotesParticipant {
  id: string;
  name: string;
  profile_photo_path?: string | null;
}

/** A user that can be @mentioned in the document. */
export interface NotesMentionUser {
  id: string;
  name: string;
  profile_photo_path?: string | null;
}

export interface CollaborativeDocUser {
  id: string | number;
  name: string;
}

export interface CollaborativeDocumentOptions {
  /** Presence channel name, e.g. `agenda-item-notes.{id}`. */
  channelName: string;
  /** URL returning `{ data: { yjs_state, ... } }` for the durable snapshot. */
  showUrl: string;
  /** URL accepting a PUT with the snapshot payload. */
  persistUrl: string;
  /** Request/response key holding the rendered HTML snapshot (e.g. `notes_html`). */
  htmlField: string;
  currentUser: CollaborativeDocUser;
  /** Extract extra data (e.g. mention pool) from the hydrate response. */
  onHydrated?: (data: Record<string, unknown>) => void;
}

const SAVE_DEBOUNCE_MS = 2000;
const HYDRATE_ORIGIN = 'hydrate';

/** Deterministic, pleasant cursor color from a user id. */
export function colorForUser(id: string | number): string {
  const palette = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
  const str = String(id);
  let hash = 0;
  for (let i = 0; i < str.length; i += 1) {
    hash = (hash * 31 + str.charCodeAt(i)) % palette.length;
  }
  return palette[Math.abs(hash)];
}

function csrfToken(): string {
  return ((usePage().props as { csrf_token?: string }).csrf_token) ?? '';
}

export function useCollaborativeDocument(options: CollaborativeDocumentOptions) {
  const { channelName, showUrl, persistUrl, htmlField, currentUser, onHydrated } = options;

  const doc = new Y.Doc();
  const awareness = new Awareness(doc);
  awareness.setLocalStateField('user', {
    name: currentUser.name,
    color: colorForUser(currentUser.id),
  });

  const provider = shallowRef<ReverbYjsProvider | null>(null);
  const participants = ref<NotesParticipant[]>([]);
  const connectionState = ref<'idle' | 'connecting' | 'connected' | 'error'>('idle');
  const saveStatus = ref<NotesSaveStatus>('idle');
  const isHydrating = ref(true);
  const contentHtml = ref<string>('');

  // The mounted editor reports its current HTML here so we can store a snapshot
  // alongside the CRDT state.
  let currentHtml = '';
  const setHtml = (html: string) => {
    currentHtml = html;
    contentHtml.value = html;
  };

  let saveTimer: ReturnType<typeof setTimeout> | null = null;
  let dirty = false;
  let channel: any = null;
  let destroyed = false;

  function bytesToBase64(bytes: Uint8Array): string {
    let binary = '';
    const chunkSize = 0x8000;
    for (let i = 0; i < bytes.length; i += chunkSize) {
      binary += String.fromCharCode(...bytes.subarray(i, i + chunkSize));
    }
    return btoa(binary);
  }

  function base64ToBytes(base64: string): Uint8Array {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
      bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
  }

  /**
   * Persist the current snapshot. Pass `keepalive` from unload/visibility
   * handlers so the request reliably completes even as the page goes away
   * (keepalive preserves our PUT + CSRF header, unlike sendBeacon).
   */
  async function persist(opts: { keepalive?: boolean } = {}): Promise<void> {
    if (destroyed) {
      return;
    }
    saveStatus.value = 'saving';
    try {
      const response = await fetch(persistUrl, {
        method: 'PUT',
        credentials: 'same-origin',
        keepalive: opts.keepalive ?? false,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({
          yjs_state: bytesToBase64(Y.encodeStateAsUpdate(doc)),
          [htmlField]: currentHtml,
        }),
      });
      if (response.ok) {
        dirty = false;
      }
      saveStatus.value = response.ok ? 'saved' : 'dirty';
    }
    catch {
      saveStatus.value = 'dirty';
    }
  }

  function scheduleSave(): void {
    dirty = true;
    saveStatus.value = 'dirty';
    if (saveTimer) {
      clearTimeout(saveTimer);
    }
    saveTimer = setTimeout(() => { void persist(); }, SAVE_DEBOUNCE_MS);
  }

  /** Immediately flush any unsaved changes (navigation / unmount / tab hidden). */
  function flush(opts: { keepalive?: boolean } = {}): void {
    if (saveTimer) {
      clearTimeout(saveTimer);
      saveTimer = null;
    }
    if (dirty) {
      void persist(opts);
    }
  }

  // Only the client that authored a change persists it. Remote peer updates
  // (origin === provider) and the initial hydrate are skipped — the authoring
  // client's snapshot is authoritative and avoids a save-per-peer storm.
  const onLocalUpdate = (_update: Uint8Array, origin: unknown) => {
    if (origin === HYDRATE_ORIGIN || (provider.value && origin === provider.value)) {
      return;
    }
    scheduleSave();
  };
  doc.on('update', onLocalUpdate);

  async function hydrate(): Promise<void> {
    try {
      const response = await fetch(showUrl, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (response.ok) {
        const json = await response.json();
        const state = json?.data?.yjs_state as string | null | undefined;
        if (state) {
          Y.applyUpdate(doc, base64ToBytes(state), HYDRATE_ORIGIN);
        }
        if (typeof json?.data?.[htmlField] === 'string') {
          contentHtml.value = json.data[htmlField];
          currentHtml = json.data[htmlField];
        }
        if (json?.data && typeof json.data === 'object') {
          onHydrated?.(json.data as Record<string, unknown>);
        }
      }
    }
    finally {
      isHydrating.value = false;
    }
  }

  function syncParticipants(members: NotesParticipant[]): void {
    const unique = new Map<string, NotesParticipant>();
    members.forEach(member => unique.set(String(member.id), member));
    participants.value = [...unique.values()];
  }

  async function connect(): Promise<void> {
    connectionState.value = 'connecting';
    try {
      const { initEcho } = await import('@/echo');
      const echo = initEcho();

      channel = echo.join(channelName);

      const reverbProvider = new ReverbYjsProvider(doc, channel, awareness);
      reverbProvider.connect();
      provider.value = reverbProvider;

      channel
        .here((members: NotesParticipant[]) => {
          syncParticipants(members);
          connectionState.value = 'connected';
          // Pull anything we are missing from peers already in the room.
          if (members.length > 1) {
            reverbProvider.requestSync();
          }
        })
        .joining((member: NotesParticipant) => {
          syncParticipants([...participants.value, member]);
        })
        .leaving((member: NotesParticipant) => {
          participants.value = participants.value.filter(p => String(p.id) !== String(member.id));
        })
        .error(() => {
          connectionState.value = 'error';
        });
    }
    catch {
      connectionState.value = 'error';
    }
  }

  // Use keepalive so the save survives the page being torn down (tab close,
  // navigation, mobile backgrounding) — this is what closes the "edits in the
  // last debounce window are lost on close" gap, including when the sole editor
  // leaves a shared session.
  const onBeforeUnload = () => flush({ keepalive: true });
  const onVisibilityChange = () => {
    if (typeof document !== 'undefined' && document.visibilityState === 'hidden') {
      flush({ keepalive: true });
    }
  };
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', onBeforeUnload);
    document.addEventListener('visibilitychange', onVisibilityChange);
  }

  void hydrate();
  void connect();

  function destroy(): void {
    if (destroyed) {
      return;
    }
    // Persist while still "alive" — persist() bails once `destroyed` is set.
    flush({ keepalive: true });
    destroyed = true;
    if (typeof window !== 'undefined') {
      window.removeEventListener('beforeunload', onBeforeUnload);
      document.removeEventListener('visibilitychange', onVisibilityChange);
    }
    doc.off('update', onLocalUpdate);
    provider.value?.destroy();
    if (channel) {
      const echoPromise = import('@/echo');
      void echoPromise.then(({ getEcho }) => getEcho()?.leave(channelName));
    }
    doc.destroy();
  }

  onScopeDispose(destroy);

  return {
    doc,
    awareness,
    provider,
    participants,
    connectionState,
    saveStatus,
    isHydrating,
    contentHtml,
    currentUserColor: colorForUser(currentUser.id),
    setHtml,
    flush,
    destroy,
  };
}
