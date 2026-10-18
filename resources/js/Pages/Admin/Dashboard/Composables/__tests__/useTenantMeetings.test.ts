import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { useTenantMeetings } from '../useTenantMeetings';
import type { AtstovavimasTenantMeeting } from '../../types';

function makeMeeting(id: string, startTime: string): AtstovavimasTenantMeeting {
  return {
    id,
    institution_id: 'inst-1',
    institution: 'Institution',
    start_time: startTime,
    title: `Meeting ${id}`,
    completion_status: 'complete',
    agenda_items: [],
    agenda_items_count: 0,
    has_report: false,
    has_protocol: false,
    type_slug: 'in-person',
  };
}

const fetchedUrls: string[] = [];
let responseQueue: AtstovavimasTenantMeeting[][] = [];

function mockFetch() {
  vi.stubGlobal('fetch', vi.fn(async (url: string | URL | Request) => {
    fetchedUrls.push(String(url));
    const data = responseQueue.shift() ?? [];

    return {
      ok: true,
      json: async () => ({ success: true, data }),
    } as Response;
  }));
}

describe('useTenantMeetings', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    fetchedUrls.splice(0);
    responseQueue = [];
    mockFetch();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('fetches the visible range with buffer, quantized to month boundaries', async () => {
    const store = useTenantMeetings(() => ['1', '2']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    expect(fetch).not.toHaveBeenCalled();

    await vi.advanceTimersByTimeAsync(200);

    expect(fetch).toHaveBeenCalledTimes(1);
    const url = fetchedUrls[0]!;
    expect(url).toContain('api.v1.admin.visak.meetings');
    // June 15 - 30d buffer = May 16 -> month start May 1
    expect(url).toContain('from=2026-05-01');
    // July 15 + 30d buffer = Aug 14 -> month end Aug 31
    expect(url).toContain('until=2026-08-31');
    expect(url).toContain('tenant_ids');
  });

  it('skips ranges that are already covered', async () => {
    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetch).toHaveBeenCalledTimes(1);

    // A range inside the already-loaded window must not trigger a fetch
    store.ensureRange(new Date(2026, 5, 20), new Date(2026, 6, 1));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  it('fetches only the missing month segments of partially covered ranges', async () => {
    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetch).toHaveBeenCalledTimes(1);

    // Extend into autumn: [2026-05-01, 2026-08-31] is loaded, so only Sep-Nov is missing
    store.ensureRange(new Date(2026, 8, 15), new Date(2026, 9, 15));
    await vi.advanceTimersByTimeAsync(200);

    expect(fetch).toHaveBeenCalledTimes(2);
    expect(fetchedUrls[1]).toContain('from=2026-09-01');
    expect(fetchedUrls[1]).toContain('until=2026-11-30');
  });

  it('dedupes meetings by id across overlapping windows and sorts by start_time', async () => {
    responseQueue = [
      [makeMeeting('b', '2026-06-10T10:00:00Z'), makeMeeting('a', '2026-05-05T10:00:00Z')],
      [makeMeeting('b', '2026-06-10T10:00:00Z'), makeMeeting('c', '2026-09-02T10:00:00Z')],
    ];

    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);

    store.ensureRange(new Date(2026, 8, 15), new Date(2026, 9, 15));
    await vi.advanceTimersByTimeAsync(200);

    expect(store.meetings.value.map(m => m.id)).toEqual(['a', 'b', 'c']);
    expect(store.meetings.value[0]!.start_time).toBeInstanceOf(Date);
  });

  it('re-fetches all loaded windows with refresh=1 on refresh()', async () => {
    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);

    // Adjacent windows merge, so this becomes one [May, Nov] window
    store.ensureRange(new Date(2026, 8, 15), new Date(2026, 9, 15));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetch).toHaveBeenCalledTimes(2);

    await store.refresh();

    expect(fetch).toHaveBeenCalledTimes(3);
    expect(fetchedUrls[2]).toContain('refresh=1');
    expect(fetchedUrls[2]).toContain('from=2026-05-01');
    expect(fetchedUrls[2]).toContain('until=2026-11-30');
  });

  it('leaves a window unloaded and reports lastError when the request fails', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => ({
      ok: false,
      status: 500,
      json: async () => ({}),
    } as Response)));

    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);

    expect(store.meetings.value).toEqual([]);
    expect(store.lastError.value).not.toBeNull();

    // The failed window was never marked as loaded, so the same range is retried
    mockFetch();
    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);

    expect(fetch).toHaveBeenCalledTimes(1);
  });

  it('reset() clears meetings and windows so ranges are fetched again', async () => {
    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetch).toHaveBeenCalledTimes(1);

    store.reset();
    expect(store.meetings.value).toEqual([]);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetch).toHaveBeenCalledTimes(2);
  });

  it('loads a zoomed-out range to its edges in chunks, nearest the middle first', async () => {
    const store = useTenantMeetings(() => ['1']);

    // About three years on screen, as at one pixel per day.
    store.ensureRange(new Date(2024, 0, 1), new Date(2026, 11, 31));
    await vi.advanceTimersByTimeAsync(200);

    const windows = fetchedUrls.map((url) => {
      const params = new URL(url, 'http://localhost').searchParams;
      return [params.get('from')!, params.get('until')!] as const;
    });
    const froms = windows.map(([from]) => from).sort();
    const untils = windows.map(([, until]) => until).sort();

    // A quarter of the span is padded on each side; nothing in between is skipped.
    expect(froms[0]! <= '2023-04-30').toBe(true);
    expect(untils.at(-1)! >= '2027-09-30').toBe(true);
    expect(windows.every(([from, until]) => new Date(until).getTime() - new Date(from).getTime() < 186 * 24 * 60 * 60 * 1000)).toBe(true);
    // The first request is the chunk around the middle of the view (mid-2025).
    expect(windows[0]![0] <= '2025-07-01' && windows[0]![1] >= '2025-06-01').toBe(true);

    // Everything is loaded now, so the same view asks for nothing more.
    fetchedUrls.splice(0);
    store.ensureRange(new Date(2024, 0, 1), new Date(2026, 11, 31));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetchedUrls).toHaveLength(0);
  });
});
