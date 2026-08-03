import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useActivityLog } from '@/Composables/useActivityLog';

const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

function jsonResponse(body: unknown, ok = true) {
  return {
    ok,
    status: ok ? 200 : 500,
    json: () => Promise.resolve(body),
  };
}

function makeEntry(id: number) {
  return {
    id,
    event: 'created' as const,
    created_at: '2026-01-15T10:00:00Z',
    causer: null,
    subject: { type: 'meeting', id: '1', label: 'Posėdis', is_root: true },
    changes: [],
  };
}

describe('useActivityLog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage({ csrf_token: 'test-csrf-token' }));
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('load() populates entries and cursor from the response', async () => {
    mockFetch.mockResolvedValueOnce(jsonResponse({
      success: true,
      data: [makeEntry(1), makeEntry(2)],
      meta: { cursor: { next: 'abc', prev: null, per_page: 25, has_more: true } },
    }));

    const { load, entries, hasMore, hasLoadedOnce } = useActivityLog('meeting', '1');
    await load();

    expect(entries.value).toHaveLength(2);
    expect(hasMore.value).toBe(true);
    expect(hasLoadedOnce.value).toBe(true);
  });

  test('loadMore() appends to existing entries rather than replacing them', async () => {
    mockFetch.mockResolvedValueOnce(jsonResponse({
      success: true,
      data: [makeEntry(1)],
      meta: { cursor: { next: 'page2', prev: null, per_page: 25, has_more: true } },
    }));

    const { load, loadMore, entries, hasMore } = useActivityLog('meeting', '1');
    await load();

    mockFetch.mockResolvedValueOnce(jsonResponse({
      success: true,
      data: [makeEntry(2)],
      meta: { cursor: { next: null, prev: 'page1', per_page: 25, has_more: false } },
    }));

    await loadMore();

    expect(entries.value.map(e => e.id)).toEqual([1, 2]);
    expect(hasMore.value).toBe(false);
  });

  test('loadMore() is a no-op when there is no next cursor', async () => {
    mockFetch.mockResolvedValueOnce(jsonResponse({
      success: true,
      data: [makeEntry(1)],
      meta: { cursor: { next: null, prev: null, per_page: 25, has_more: false } },
    }));

    const { load, loadMore } = useActivityLog('meeting', '1');
    await load();

    mockFetch.mockClear();
    await loadMore();

    expect(mockFetch).not.toHaveBeenCalled();
  });

  test('a { success: false } response surfaces as an error without appending entries', async () => {
    mockFetch.mockResolvedValueOnce(jsonResponse({ success: false, message: 'Forbidden' }));

    const { load, entries, error } = useActivityLog('meeting', '1');
    await load();

    expect(entries.value).toHaveLength(0);
    expect(error.value).toBe('Forbidden');
  });

  test('reset() clears entries, cursor, and hasLoadedOnce', async () => {
    mockFetch.mockResolvedValueOnce(jsonResponse({
      success: true,
      data: [makeEntry(1)],
      meta: { cursor: { next: null, prev: null, per_page: 25, has_more: false } },
    }));

    const { load, reset, entries, hasMore, hasLoadedOnce } = useActivityLog('meeting', '1');
    await load();
    reset();

    expect(entries.value).toHaveLength(0);
    expect(hasMore.value).toBe(false);
    expect(hasLoadedOnce.value).toBe(false);
  });

  test('setFilters() resets state and reloads with the new filters applied to the request URL', async () => {
    mockFetch.mockResolvedValue(jsonResponse({
      success: true,
      data: [makeEntry(1)],
      meta: { cursor: { next: null, prev: null, per_page: 25, has_more: false } },
    }));

    const { setFilters, filters } = useActivityLog('meeting', '1');
    setFilters({ scope: 'self' });
    await Promise.resolve();
    await Promise.resolve();

    expect(filters.value.scope).toBe('self');
    const calledUrl = mockFetch.mock.calls.at(-1)?.[0] as string;
    expect(calledUrl).toContain('scope=self');
  });
});
