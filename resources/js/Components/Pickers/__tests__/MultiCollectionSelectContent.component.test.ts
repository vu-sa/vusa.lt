import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { commonStubs } from '@/tests/stubs';
import type { AdminSearchCollectionId } from '@/Components/Search/adminSearchCollections';
import type { MultiSearchResults } from '@/Search/core/types';

import MultiCollectionSelectContent from '../MultiCollectionSelectContent.vue';

const multiSearch = vi.fn<() => Promise<MultiSearchResults<AdminSearchCollectionId>>>();

vi.mock('@/Composables/useAdminSearch', () => ({
  useAdminSearch: () => ({ multiSearch, isRateLimited: { value: false } }),
}));

/** The browse-mode search is debounced, so drive the timer rather than waiting on it. */
async function mountAndSearch(collections: AdminSearchCollectionId[]) {
  const wrapper = mountContent(collections);
  await vi.advanceTimersByTimeAsync(400);
  return wrapper;
}

function mountContent(collections: AdminSearchCollectionId[]) {
  return mount(MultiCollectionSelectContent, {
    props: {
      collections,
      multiple: true,
      selectedIds: new Set<string>(),
      emptyMessage: 'empty',
      searchPlaceholder: 'search',
    },
    global: { stubs: commonStubs },
  });
}

describe('MultiCollectionSelectContent', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    multiSearch.mockReset().mockResolvedValue({});
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('asks only for the collections it was told to offer', async () => {
    await mountAndSearch(['users']);

    expect(multiSearch).toHaveBeenCalledWith('', [{ id: 'users', limit: 15 }]);
  });

  it('renders an agenda item returned under its backend collection ID', async () => {
    multiSearch.mockResolvedValue({
      agenda_items: { hits: [{ id: 'a1', title: 'Posėdžio klausimas' }], found: 1 },
    });

    const wrapper = await mountAndSearch(['agenda_items']);

    expect(multiSearch).toHaveBeenCalledWith('', [{ id: 'agenda_items', limit: 15 }]);
    expect(wrapper.get('[data-collection="agenda_items"]').text()).toContain('Posėdžio klausimas');
    wrapper.unmount();
  });
});
