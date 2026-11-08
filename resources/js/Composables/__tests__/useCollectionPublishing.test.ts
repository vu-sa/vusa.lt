import { router } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';

import { publishChoices, useCollectionPublishing } from '../useCollectionPublishing';

import type { CollectionSource } from '../useCollectionSource';

it('blocks overlapping writes and deletions while other rows remain usable', () => {
  vi.mocked(router.patch).mockClear();
  const undo = vi.fn();
  const source = { patchItems: vi.fn(() => undo), hideItems: vi.fn(() => undo), refresh: vi.fn() };
  const publishing = useCollectionPublishing({
    routePrefix: 'news', source: source as unknown as CollectionSource<{ id: number; published: boolean }>,
    isPublished: item => item.published, patchFor: published => ({ published }),
  });
  publishing.setPublished(['1'], true);
  publishing.setPublished(['1', '2'], false);
  publishing.setPublished(['2'], true);
  expect(router.patch).toHaveBeenCalledTimes(2);
  publishing.pendingDelete.value = { ids: ['1'], clear: vi.fn() };
  publishing.confirmDelete();
  expect(source.hideItems).not.toHaveBeenCalled();
  const options = vi.mocked(router.patch).mock.calls[0]![2]!;
  options.onNetworkError?.(new Error('Offline'));
  options.onFinish?.({} as never);
  expect(undo).toHaveBeenCalledOnce();
  expect(publishing.isPending(['1'])).toBe(false);
  expect(publishing.isPending(['2'])).toBe(true);
  publishing.setPublished(['1'], false);
  expect(router.patch).toHaveBeenCalledTimes(3);
});

interface Row {
  published: boolean;
}

const isPublished = (row: Row) => row.published;

describe('publishChoices', () => {
  it('offers only "to drafts" when every selected row is published', () => {
    expect(publishChoices<Row>([{ published: true }, { published: true }], isPublished)).toEqual({ publish: false, draft: true });
  });

  it('offers only "publish" when every selected row is a draft', () => {
    expect(publishChoices<Row>([{ published: false }], isPublished)).toEqual({ publish: true, draft: false });
  });

  it('offers both for a mixed selection', () => {
    expect(publishChoices<Row>([{ published: true }, { published: false }], isPublished)).toEqual({ publish: true, draft: true });
  });
});
