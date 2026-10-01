import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, reactive } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';
import type { InertiaForm } from '@inertiajs/vue3';
import { useContentEditor, blankTranslation, cloneTranslation, type ContentEditorData } from '../useContentEditor';

const http = vi.hoisted(() => ({ get: vi.fn(), put: vi.fn(), delete: vi.fn(), request: vi.fn(), isError: vi.fn(() => false) }));
const storage = vi.hoisted(() => vi.fn());
vi.mock('../contentEditorHttp', () => ({ contentEditorHttp: http }));
vi.mock('../contentEditorStorage', () => ({ recoveryStorage: storage }));

const wrappers: ReturnType<typeof mount>[] = [];
function session(data: ContentEditorData = { id: 1, title: 'Original', content_version: 'old', content: { parts: [] } }) {
  const state = reactive({ ...data, processing: false, errors: {} });
  const fields = Object.keys(data);
  const form = Object.assign(state, {
    data: () => Object.fromEntries(fields.map(field => [field, state[field as keyof typeof state]])),
    defaults: vi.fn(), clearErrors: vi.fn(), setError: vi.fn(),
  }) as unknown as InertiaForm<ContentEditorData>;
  let editor!: ReturnType<typeof useContentEditor>;
  wrappers.push(mount(defineComponent({ setup() { editor = useContentEditor('pages', form, { stay: true }); return () => null; } })));
  return { form, get editor() { return editor; } };
}

beforeEach(() => {
  vi.clearAllMocks();
  localStorage.clear();
  http.get.mockResolvedValue({ data: { data: null } });
  http.put.mockResolvedValue({ data: { data: { revision: 1 } } });
  http.delete.mockResolvedValue({ data: { data: null } });
  storage.mockResolvedValue(undefined);
});
afterEach(() => { wrappers.splice(0).forEach(wrapper => wrapper.unmount()); vi.restoreAllMocks(); });

describe('content recovery and saves', () => {
  it('opens a new editor when browser storage is blocked', async () => {
    vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new DOMException('Blocked', 'SecurityError'); });
    const active = session({ title: '', content: { parts: [] } });
    await flushPromises();
    expect(active.editor.ready.value).toBe(true);
    expect(http.get).toHaveBeenCalled();
  });

  it('keeps edits and reports failure when the current record cannot be loaded', async () => {
    const active = session();
    await flushPromises();
    active.form.title = 'Still editing';
    http.get.mockRejectedValue(new Error('Offline'));
    await active.editor.loadCurrent();
    expect(active.form.title).toBe('Still editing');
    expect(active.form.content_version).toBe('old');
    expect(active.editor.saveError.value).toBe('Error: Offline');
  });

  it('offers distinct device and server copies without overwriting them', async () => {
    storage.mockResolvedValue({ snapshot: { title: 'Device' }, updated_at: new Date().toISOString() });
    http.get.mockResolvedValue({ data: { data: { snapshot: { title: 'Server' }, updated_at: new Date().toISOString(), revision: 4 } } });
    const active = session();
    await flushPromises();
    expect(active.editor.copies.value.map(copy => copy.source)).toEqual(['local', 'server']);
    expect(active.form.title).toBe('Original');
    expect(active.editor.ready.value).toBe(false);
    expect(http.put).not.toHaveBeenCalled();
  });

  it('preserves typing during a save and retains recovery for those newer changes', async () => {
    let finish!: (value: unknown) => void;
    http.request.mockImplementation(() => new Promise(resolve => { finish = resolve; }));
    const active = session();
    await flushPromises();
    active.form.title = 'Sent';
    const saving = active.editor.save();
    await flushPromises();
    active.form.title = 'Newer';
    finish({ data: { data: { id: 1, title: 'Sent', content_version: 'new', content: { parts: [] } } } });
    await saving;
    expect(active.form.title).toBe('Newer');
    expect(active.form.defaults).toHaveBeenCalledWith(expect.objectContaining({ title: 'Sent' }));
    expect(http.delete).not.toHaveBeenCalled();
    expect(storage).toHaveBeenCalledWith(expect.any(String), 'put', expect.objectContaining({ snapshot: expect.objectContaining({ title: 'Newer' }) }));
  });

  it('keeps failed edits and does not change their saved baseline', async () => {
    http.request.mockRejectedValue(new Error('Offline'));
    const active = session();
    await flushPromises();
    active.form.title = 'Unfinished';
    await active.editor.save();
    expect(active.form.defaults).not.toHaveBeenCalled();
    expect(active.form.title).toBe('Unfinished');
    expect(http.delete).not.toHaveBeenCalled();
    expect(active.editor.saveError.value).toContain('Offline');
  });

  it('reconciles new block ids by their client key', async () => {
    const active = session({ id: 1, title: 'Original', content_version: 'old', content: { parts: [{ key: 'new-block', type: 'tiptap', json_content: {} }] } });
    await flushPromises();
    active.form.title = 'Saved';
    http.request.mockResolvedValue({ data: { data: { id: 1, title: 'Saved', content_version: 'new', content: { parts: [{ id: 42, key: 'new-block' }] } } } });
    await active.editor.save();
    expect(active.form.content?.parts[0]?.id).toBe(42);
  });

  it('acknowledges a language link without clearing unsaved source edits', async () => {
    const active = session({ id: 1, title: 'Original', content_version: 'old', other_lang_id: null, meta_description: '', highlights: [], content: { parts: [] } });
    await flushPromises();
    active.form.title = 'Still editing';
    active.editor.acknowledgePairing({ id: 1, title: 'Original', content_version: 'paired', other_lang_id: 2, meta_description: null, highlights: null, content: { parts: [] } });
    expect(active.form.title).toBe('Still editing');
    expect(active.form.content_version).toBe('paired');
    expect(active.form.defaults).toHaveBeenCalledWith({ content_version: 'paired', other_lang_id: 2 });
    active.editor.acknowledgePairing({ id: 1, title: 'Someone else changed this', content_version: 'external', other_lang_id: 2, content: { parts: [] } });
    expect(active.form.content_version).toBe('paired');
  });

  it('updates an already-created record recovered through its creation form', async () => {
    storage.mockResolvedValue({ snapshot: { id: 7, title: 'Recovered', content_version: 'saved', content: { parts: [] } }, updated_at: new Date().toISOString() });
    const active = session({ id: undefined, title: '', content_version: undefined, content: { parts: [] } });
    await flushPromises();
    await active.editor.restore(0);
    http.request.mockResolvedValue({ data: { data: { id: 7, title: 'Recovered', content_version: 'next', content: { parts: [] } } } });
    await active.editor.save();
    expect(http.request).toHaveBeenCalledWith(expect.objectContaining({ method: 'patch' }));
    expect(active.editor.currentId.value).toBe(7);
  });

  it('copies current translation content with fresh block identities and independent publication', () => {
    const source: ContentEditorData = { id: 9, title: 'Current text', lang: 'lt', is_active: true, content_version: 'old', tags: [4], image: '/cover.jpg', translated_parent_id: 5, content: { parts: [{ id: 8, key: 'original', type: 'tiptap', json_content: { text: 'Unsaved edits' } }] } };
    const copy = cloneTranslation(source);
    expect(copy).toMatchObject({ title: 'Current text', lang: 'en', other_lang_id: 9, is_active: false, draft: true, tags: [4], parent_id: 5 });
    expect(copy.id).toBeUndefined();
    expect(copy.content?.parts[0]?.id).toBeUndefined();
    expect(copy.content?.parts[0]?.key).not.toBe('original');
    expect(copy.content?.parts[0]?.json_content).toEqual({ text: 'Unsaved edits' });
    expect(source.content?.parts[0]?.id).toBe(8);
  });

  it('starts a new language version blank while keeping the settings it inherits', () => {
    const source: ContentEditorData = { id: 9, title: 'Current text', lang: 'lt', draft: false, short: '<p>Intro</p>', highlights: ['One'], tags: [4], image: '/cover.jpg', content: { parts: [{ id: 8, type: 'tiptap', json_content: { text: 'Body' } }] } };
    const blank = blankTranslation(source);
    expect(blank).toMatchObject({ title: '', short: '', highlights: [], lang: 'en', other_lang_id: 9, draft: true, tags: [4], image: '/cover.jpg' });
    expect(blank.content?.parts).toHaveLength(1);
    expect(blank.content?.parts[0]).toMatchObject({ type: 'tiptap', json_content: {} });
    expect('meta_description' in blank).toBe(false);
  });
});
