import { beforeEach, describe, expect, it, vi } from 'vitest';

import { getFilesJson } from '../fileApi';
import { useFileListing } from '../useFileListing';
import { useFileSearch } from '../useFileSearch';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('../fileApi', () => ({ getFilesJson: vi.fn() }));

const payload = (name: string) => ({
  data: {
    files: [{ path: `public/files/${name}`, name, type: 'file' as const, size: 1, modified: 1 }],
    directories: [],
    path: `public/files/${name}`,
  },
  meta: {},
});

beforeEach(() => vi.mocked(getFilesJson).mockReset());

describe('file request ordering', () => {
  it('keeps the latest search results when an older response finishes last', async () => {
    let resolveOld!: (value: ReturnType<typeof payload>) => void;
    vi.mocked(getFilesJson)
      .mockImplementationOnce(() => new Promise(resolve => { resolveOld = resolve; }))
      .mockResolvedValueOnce(payload('latest'));
    const search = useFileSearch();

    const old = search.search('older', 'public/files');
    await search.search('latest', 'public/files');
    resolveOld(payload('older'));
    await old;

    expect(search.results.value?.map(file => file.name)).toEqual(['latest']);
    expect(search.searching.value).toBe(false);
  });

  it('ignores a search response after clearing the query', async () => {
    let resolveSearch!: (value: ReturnType<typeof payload>) => void;
    vi.mocked(getFilesJson).mockImplementationOnce(() => new Promise(resolve => { resolveSearch = resolve; }));
    const search = useFileSearch();
    const pending = search.search('older', 'public/files');

    search.clear();
    resolveSearch(payload('older'));
    await pending;

    expect(search.results.value).toBeNull();
    expect(search.searching.value).toBe(false);
  });

  it('keeps the latest folder when the initial listing finishes last', async () => {
    let resolveInitial!: (value: ReturnType<typeof payload>) => void;
    vi.mocked(getFilesJson)
      .mockImplementationOnce(() => new Promise(resolve => { resolveInitial = resolve; }))
      .mockResolvedValueOnce(payload('latest'));
    const listing = useFileListing();

    await listing.fetch('public/files/latest');
    resolveInitial(payload('older'));
    await Promise.resolve();
    await Promise.resolve();

    expect(listing.currentPath.value).toBe('public/files/latest');
    expect(listing.filesRaw.value.map(file => file.name)).toEqual(['latest']);
    expect(listing.loading.value).toBe(false);
  });
});
