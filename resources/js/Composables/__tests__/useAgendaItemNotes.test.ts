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
    localStorage.clear();
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

  it('reviews an unsaved draft before merging it into the reopened note', async () => {
    vi.mocked(fetch).mockImplementation(async (_, init) => init?.method === 'PUT'
      ? json(503, {})
      : json(200, { success: true, data: { version: 1 } }));
    const first = setup();
    await vi.advanceTimersByTimeAsync(1);
    first.notes.doc.getText('t').insert(0, 'Darbotvarkė');
    first.scope.stop();

    const second = setup();
    await vi.advanceTimersByTimeAsync(2500);
    expect(second.notes.recoveredDraft.value).toBeTruthy();
    expect(second.notes.doc.getText('t').toString()).toBe('');
    await second.notes.resolveDraft(true);
    expect(second.notes.doc.getText('t').toString()).toBe('Darbotvarkė');
    second.scope.stop();
  });

  it('clears a stale local draft when the server snapshot already contains its updates', async () => {
    let savedState = '';
    vi.mocked(fetch).mockImplementation(async (_, init) => {
      if (init?.method === 'PUT') {
        savedState = (JSON.parse(String(init.body)) as { yjs_state: string }).yjs_state;
        // Simulate tab teardown before the response clears localStorage.
        return new Promise<Response>(() => {});
      }
      return json(200, { success: true, data: { yjs_state: savedState || null, version: savedState ? 2 : 1 } });
    });

    const first = setup();
    await vi.advanceTimersByTimeAsync(1);
    first.notes.doc.getText('t').insert(0, 'Išsaugota');
    first.scope.stop();
    expect(savedState).toBeTruthy();

    const second = setup();
    await vi.advanceTimersByTimeAsync(1);
    expect(second.notes.recoveredDraft.value).toBe('');
    expect(localStorage.getItem('agenda-note:u1:item-1')).toBeNull();
    expect(second.notes.doc.getText('t').toString()).toBe('Išsaugota');
    second.scope.stop();
  });

  it('formats multi-paragraph and mention previews, discards drafts, and clears corrupt storage', async () => {
    const previewDoc = new Y.Doc();
    const fragment = previewDoc.getXmlFragment('default');
    const p1 = new Y.XmlElement('paragraph');
    p1.insert(0, [new Y.XmlText('Pirma eilutė ')]);
    const mention = new Y.XmlElement('mention');
    mention.setAttribute('label', 'Jonas');
    p1.insert(1, [mention]);
    const p2 = new Y.XmlElement('paragraph');
    p2.insert(0, [new Y.XmlText('Antra eilutė')]);
    fragment.insert(0, [p1, p2]);
    localStorage.setItem('agenda-note:u1:item-1', JSON.stringify({
      time: 1700000000000,
      state: toBase64(Y.encodeStateAsUpdate(previewDoc)),
    }));
    previewDoc.destroy();

    vi.mocked(fetch).mockResolvedValue(json(200, { success: true, data: { version: 1 } }));
    const first = setup();
    await vi.advanceTimersByTimeAsync(1);
    expect(first.notes.recoveredText.value).toBe('Pirma eilutė @Jonas\nAntra eilutė');
    await first.notes.resolveDraft(false);
    expect(first.notes.recoveredDraft.value).toBe('');
    expect(localStorage.getItem('agenda-note:u1:item-1')).toBeNull();
    first.scope.stop();

    localStorage.setItem('agenda-note:u1:item-1', '{not-json');
    const second = setup();
    await vi.advanceTimersByTimeAsync(1);
    expect(second.notes.recoveredDraft.value).toBe('');
    expect(localStorage.getItem('agenda-note:u1:item-1')).toBeNull();
    second.scope.stop();
  });

  it('persists synced HTML when the async editor mounts after restoring a draft', async () => {
    vi.mocked(fetch).mockImplementation(async (_, init) => init?.method === 'PUT'
      ? json(503, {})
      : json(200, { success: true, data: { notes_html: '<p>Sena</p>', version: 1 } }));
    const first = setup();
    await vi.advanceTimersByTimeAsync(1);
    first.notes.doc.getText('t').insert(0, 'Nauja');
    first.scope.stop();

    vi.mocked(fetch).mockImplementation(async (_, init) => init?.method === 'PUT'
      ? json(200, { success: true, data: { version: 2 } })
      : json(200, { success: true, data: { notes_html: '<p>Sena</p>', version: 1 } }));
    const second = setup();
    await vi.advanceTimersByTimeAsync(1);
    await second.notes.resolveDraft(true);
    second.notes.setHtml('<p>Sena</p><p>Nauja</p>');
    await vi.advanceTimersByTimeAsync(2500);

    const lastSave = vi.mocked(fetch).mock.calls
      .filter(([, init]) => init?.method === 'PUT')
      .map(([, init]) => JSON.parse(String(init?.body)) as { notes_html: string })
      .at(-1);
    expect(lastSave?.notes_html).toBe('<p>Sena</p><p>Nauja</p>');
    second.scope.stop();
  });

  it('saves against the loaded version, and on a conflict merges the stored notes before saving again', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(json(200, { success: true, data: { yjs_state: storedState('Mano'), version: 2 } }))
      .mockResolvedValueOnce(json(409, { success: false, code: 'notes_conflict', data: { yjs_state: storedState('Kito'), version: 3 } }))
      .mockResolvedValueOnce(json(200, { success: true, data: { version: 4 } }));
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
