import { effectScope } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import * as Y from 'yjs';

import { useAgendaItemNotes } from '../useAgendaItemNotes';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
// Realtime is out of scope here: the notes work against the API alone.
vi.mock('@/echo', () => ({
  initEcho: () => {
    throw new Error('offline');
  },
  getEcho: () => null,
}));

const toBase64 = (bytes: Uint8Array) => btoa(String.fromCharCode(...bytes));

function storedState(text: string): string {
  const doc = new Y.Doc();
  doc.getText('t').insert(0, text);
  return toBase64(Y.encodeStateAsUpdate(doc));
}

const json = (status: number, body: unknown) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });

const saves = () => vi.mocked(fetch).mock.calls
  .filter(([, init]) => init?.method === 'PUT')
  .map(([, init]) => JSON.parse(String(init?.body)) as { base_version: number | null; yjs_state: string });

function setup() {
  const scope = effectScope();
  const notes = scope.run(() => useAgendaItemNotes('item-1', { id: 'u1', name: 'Ona' }))!;
  return { notes, scope };
}

const realFetch = globalThis.fetch;

describe('useAgendaItemNotes', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    globalThis.fetch = vi.fn();
  });

  afterEach(() => {
    vi.useRealTimers();
    globalThis.fetch = realFetch;
  });

  it('keeps editing off when the stored notes could not be loaded', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(json(500, {}));
    const { notes, scope } = setup();
    await vi.advanceTimersByTimeAsync(2500);

    expect(notes.loadFailed.value).toBe(true);
    expect(notes.isHydrating.value).toBe(false);
    expect(saves()).toHaveLength(0);
    scope.stop();
  });

  it('saves against the loaded version, and on a conflict merges the stored notes before saving again', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(json(200, { data: { yjs_state: storedState('Mano'), version: 2 } }))
      .mockResolvedValueOnce(json(409, { data: { yjs_state: storedState('Kito'), version: 3 } }))
      .mockResolvedValueOnce(json(200, { data: { version: 4 } }));
    const { notes, scope } = setup();
    await vi.advanceTimersByTimeAsync(2500);

    notes.doc.getText('t').insert(0, '!');
    await vi.advanceTimersByTimeAsync(2500);
    await vi.advanceTimersByTimeAsync(2500);

    const [conflicting, merged] = saves();
    expect(conflicting!.base_version).toBe(2);
    expect(merged!.base_version).toBe(3);
    expect(notes.doc.getText('t').toString()).toContain('Kito');
    expect(notes.saveStatus.value).toBe('saved');
    scope.stop();
  });
});
