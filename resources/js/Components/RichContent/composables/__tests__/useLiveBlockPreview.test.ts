import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const fetchPreviewMock = vi.fn();

vi.mock('../useContentPartPreview', () => ({
  useContentPartPreview: () => ({ debouncedFetchPreview: fetchPreviewMock }),
}));

import { useLiveBlockPreview } from '../useLiveBlockPreview';
import type { ContentPart } from '../../Types';

function flushMicrotasks() {
  return new Promise(resolve => setTimeout(resolve, 0));
}

describe('useLiveBlockPreview', () => {
  beforeEach(() => {
    fetchPreviewMock.mockReset();
  });

  it('applies a successful preview fetch', async () => {
    const content = ref<ContentPart | undefined>({ type: 'event-list', json_content: {}, options: { limit: 3 } });
    fetchPreviewMock.mockResolvedValueOnce({ preview: { type: 'event-list', items: ['first'] } });

    const { previewResolved } = useLiveBlockPreview(content, () => 1);
    await flushMicrotasks();

    expect(previewResolved.value).toEqual({ type: 'event-list', items: ['first'] });
  });

  it('ignores a superseded debounced call (resolves to undefined) instead of crashing or clearing the preview', async () => {
    // vueuse's `useDebounceFn` (default `rejectOnCancel: false`) resolves an
    // older, cancelled call's promise to `undefined` rather than the newer call's
    // result — reproduced here directly instead of faking timers.
    const content = ref<ContentPart | undefined>({ type: 'event-list', json_content: {}, options: { limit: 3 } });
    fetchPreviewMock.mockResolvedValueOnce({ preview: { type: 'event-list', items: ['first'] } });

    const { previewResolved } = useLiveBlockPreview(content, () => 1);
    await flushMicrotasks();
    expect(previewResolved.value).toEqual({ type: 'event-list', items: ['first'] });

    fetchPreviewMock.mockResolvedValueOnce(undefined);
    content.value = { type: 'event-list', json_content: {}, options: { limit: 5 } };
    await flushMicrotasks();

    // Unchanged — not undefined (would crash the consuming display's `.items` read)
    // and not wiped to an incorrect empty state.
    expect(previewResolved.value).toEqual({ type: 'event-list', items: ['first'] });
  });

  it('never fetches for a type that is not server-resolved', async () => {
    const content = ref<ContentPart | undefined>({ type: 'tiptap', json_content: {}, options: {} });

    useLiveBlockPreview(content, () => 1);
    await flushMicrotasks();

    expect(fetchPreviewMock).not.toHaveBeenCalled();
  });
});
