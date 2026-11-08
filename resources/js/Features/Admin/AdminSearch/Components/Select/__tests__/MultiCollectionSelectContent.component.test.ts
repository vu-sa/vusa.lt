import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { commonStubs } from '@/tests/stubs';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';

import MultiCollectionSelectContent from '../MultiCollectionSelectContent.vue';

const multiSearch = vi.fn(async () => createEmptyMultiSearchResults());

vi.mock('@/Composables/useAdminSearch', () => ({
  useAdminSearch: () => ({ multiSearch, isRateLimited: { value: false } }),
}));

/** The browse-mode search is debounced, so drive the timer rather than waiting on it. */
async function mountAndSearch(collections: string[]) {
  mountContent(collections);
  await vi.advanceTimersByTimeAsync(400);
}

function mountContent(collections: string[]) {
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
    multiSearch.mockClear();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('asks only for the collections it was told to offer', async () => {
    // The limits used to be hardcoded for the link-target picker, with usersLimit: 0
    // — so a picker over users searched and always came back empty.
    await mountAndSearch(['users']);

    expect(multiSearch).toHaveBeenCalledWith('', expect.objectContaining({
      usersLimit: 15,
      pagesLimit: 0,
      newsLimit: 0,
      institutionsLimit: 0,
    }));
  });

  it('caps every collection it was not given', async () => {
    await mountAndSearch(['pages', 'news']);

    expect(multiSearch).toHaveBeenCalledWith('', expect.objectContaining({
      pagesLimit: 15,
      newsLimit: 15,
      usersLimit: 0,
      dutiesLimit: 0,
      meetingsLimit: 0,
    }));
  });
});
