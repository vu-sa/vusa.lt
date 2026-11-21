import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { useInstitutionMeetings, useTenantMeetings } from '../useTenantMeetings';
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

  const windowsOf = () => fetchedUrls.map((url) => {
    const params = new URL(url, 'http://localhost').searchParams;
    return `${params.get('from')}..${params.get('until')}`;
  });

  it('pads the visible range and asks for whole calendar half-years', async () => {
    const store = useTenantMeetings(() => ['1', '2']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    expect(fetch).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(200);

    expect(windowsOf().sort()).toEqual(['2026-01-01..2026-06-30', '2026-07-01..2026-12-31']);
    expect(new URL(fetchedUrls[0]!, 'http://localhost').searchParams.getAll('tenant_ids[]')).toEqual(['1', '2']);
  });

  it('never asks again for a half-year it already has', async () => {
    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    fetchedUrls.splice(0);

    // Scrolling a little stays inside the loaded half-years; further reaches only the next one.
    store.ensureRange(new Date(2026, 6, 1), new Date(2026, 7, 1));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetchedUrls).toHaveLength(0);

    store.ensureRange(new Date(2026, 10, 15), new Date(2026, 11, 20));
    await vi.advanceTimersByTimeAsync(200);
    expect(windowsOf()).toEqual(['2027-01-01..2027-06-30']);
  });

  it('keeps each meeting once per institution and sorts by start_time', async () => {
    responseQueue = [
      [makeMeeting('b', '2026-06-10T10:00:00Z'), makeMeeting('a', '2026-05-05T10:00:00Z'), { ...makeMeeting('a', '2026-05-05T10:00:00Z'), institution_id: 'inst-2' }],
      [makeMeeting('b', '2026-06-10T10:00:00Z'), makeMeeting('c', '2026-09-02T10:00:00Z')],
    ];
    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);

    expect(store.meetings.value.map(m => `${m.institution_id}:${m.id}`)).toEqual(['inst-1:a', 'inst-2:a', 'inst-1:b', 'inst-1:c']);
    expect(store.meetings.value[0]!.start_time).toBeInstanceOf(Date);
  });

  it('on refresh() drops what it had and reloads the view with refresh=1', async () => {
    responseQueue = [[makeMeeting('gone', '2026-05-05T10:00:00Z')]];
    const store = useTenantMeetings(() => ['1']);
    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    expect(store.meetings.value.map(m => m.id)).toEqual(['gone']);
    fetchedUrls.splice(0);

    store.refresh();
    await vi.advanceTimersByTimeAsync(0);

    expect(store.meetings.value).toEqual([]);
    expect(windowsOf().sort()).toEqual(['2026-01-01..2026-06-30', '2026-07-01..2026-12-31']);
    expect(fetchedUrls.every(url => url.includes('refresh=1'))).toBe(true);
  });

  it('leaves a failed half-year unloaded and retries it on the next report', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => ({ ok: false, status: 500, json: async () => ({}) } as Response)));
    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    expect(store.meetings.value).toEqual([]);
    expect(store.lastError.value).not.toBeNull();
    expect(store.isFetching.value).toBe(false);

    mockFetch();
    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetch).toHaveBeenCalledTimes(2);
  });

  it('reset() clears meetings and coverage so the view is fetched again', async () => {
    responseQueue = [[makeMeeting('a', '2026-06-10T10:00:00Z')]];
    const store = useTenantMeetings(() => ['1']);
    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    fetchedUrls.splice(0);

    store.reset();
    expect(store.meetings.value).toEqual([]);
    await vi.advanceTimersByTimeAsync(200);
    expect(fetchedUrls).toHaveLength(2);
  });

  it('loads a zoomed-out range to its edges in half-years, nearest the middle first', async () => {
    const store = useTenantMeetings(() => ['1']);

    // About three years on screen, as at one pixel per day.
    store.ensureRange(new Date(2024, 0, 1), new Date(2026, 11, 31));
    await vi.advanceTimersByTimeAsync(200);

    const windows = windowsOf();
    // A quarter of the span is padded on each side, rounded out to half-years; nothing in between is skipped.
    expect([...windows].sort()[0]).toBe('2023-01-01..2023-06-30');
    expect([...windows].sort().at(-1)).toBe('2027-07-01..2027-12-31');
    expect(windows).toHaveLength(10);
    // The first request is a half-year around the middle of the view (2025).
    expect(windows[0]!.startsWith('2025-')).toBe(true);

    fetchedUrls.splice(0);
    store.ensureRange(new Date(2024, 0, 1), new Date(2026, 11, 31));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetchedUrls).toHaveLength(0);
  });
});

describe('useTenantMeetings in flight', () => {
  const resolvers: Array<() => void> = [];

  beforeEach(() => {
    vi.useFakeTimers();
    fetchedUrls.splice(0);
    resolvers.splice(0);
    vi.stubGlobal('fetch', vi.fn((url: string) => {
      fetchedUrls.push(String(url));
      return new Promise<Response>((resolve) => {
        resolvers.push(() => resolve({ ok: true, json: async () => ({ success: true, data: [] }) } as Response));
      });
    }));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('fetches up to three windows at once', async () => {
    const store = useTenantMeetings(() => ['1']);

    store.ensureRange(new Date(2024, 0, 15), new Date(2027, 0, 15));
    await vi.advanceTimersByTimeAsync(200);

    expect(fetch).toHaveBeenCalledTimes(3);

    resolvers.shift()!();
    await vi.advanceTimersByTimeAsync(0);
    expect(fetch).toHaveBeenCalledTimes(4);
  });

  it('keeps loading when the chart re-emits a range that is already being fetched', async () => {
    const store = useTenantMeetings(() => ['1']);
    const from = new Date(2024, 0, 15);
    const until = new Date(2027, 0, 15);

    store.ensureRange(from, until);
    await vi.advanceTimersByTimeAsync(200);
    const planned = fetchedUrls.length;

    // A window landing re-renders the chart, which emits the same range again.
    resolvers.shift()!();
    store.ensureRange(from, until);
    await vi.advanceTimersByTimeAsync(200);

    while (resolvers.length > 0) {
      resolvers.shift()!();
      await vi.advanceTimersByTimeAsync(0);
    }
    await vi.advanceTimersByTimeAsync(200);

    const windows = fetchedUrls.map(url => new URL(url, 'http://localhost').searchParams.get('from'));
    expect(planned).toBe(3);
    expect(new Set(windows).size).toBe(windows.length);
    expect(store.isFetching.value).toBe(false);
  });
});

describe('useInstitutionMeetings', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    fetchedUrls.splice(0);
    responseQueue = [];
    mockFetch();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('asks the representative endpoint for the given institutions, a calendar year at a time', async () => {
    const store = useInstitutionMeetings(() => ['inst-1', 'inst-2']);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);

    const url = new URL(fetchedUrls[0]!, 'http://localhost');
    expect(url.pathname).toContain('api.v1.admin.atstovavimas.meetings');
    expect(url.searchParams.getAll('institution_ids[]')).toEqual(['inst-1', 'inst-2']);
    expect(url.searchParams.get('from')).toBe('2026-01-01');
    expect(url.searchParams.get('to')).toBe('2026-12-31');
    expect(fetchedUrls).toHaveLength(1);
  });

  it('keeps a joint meeting once under each of its institutions', async () => {
    const store = useInstitutionMeetings(() => ['inst-1', 'inst-2']);
    responseQueue.push([
      makeMeeting('joint', '2026-06-20T10:00:00'),
      { ...makeMeeting('joint', '2026-06-20T10:00:00'), institution_id: 'inst-2' },
    ]);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);

    expect(store.meetings.value.map(meeting => meeting.institution_id)).toEqual(['inst-1', 'inst-2']);
  });

  it('loads an unpadded first view across New Year as one request per year, in parallel', async () => {
    const store = useInstitutionMeetings(() => ['inst-1']);

    store.ensureRange(new Date(2026, 6, 21), new Date(2027, 0, 21), { pad: false });
    await vi.advanceTimersByTimeAsync(200);

    const windows = fetchedUrls.map((url) => {
      const params = new URL(url, 'http://localhost').searchParams;
      return `${params.get('from')}..${params.get('to')}`;
    });
    expect(windows.sort()).toEqual(['2026-01-01..2026-12-31', '2027-01-01..2027-12-31']);
  });

  it('does not fetch without institutions', async () => {
    const store = useInstitutionMeetings(() => []);

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);

    expect(fetch).not.toHaveBeenCalled();
  });
});

describe('tooltip agendas', () => {
  it('fetches a meeting agenda once, with the window access, and retries after a failure', async () => {
    const urls: string[] = [];
    let fail = true;
    vi.stubGlobal('fetch', vi.fn(async (url: string) => {
      urls.push(String(url));
      if (fail) {
        fail = false;
        return { ok: false, json: async () => ({}) } as Response;
      }
      return { ok: true, json: async () => ({ success: true, data: { agenda_items: [{ id: 'a1', title: 'Item' }] } }) } as Response;
    }));
    const store = useTenantMeetings(() => ['1', '2']);
    const meeting = { id: 'm1', institution_id: 'inst-1' };

    expect(await store.loadAgenda(meeting)).toBeNull();
    expect(await store.loadAgenda(meeting)).toEqual([{ id: 'a1', title: 'Item' }]);
    expect(await store.loadAgenda(meeting)).toEqual([{ id: 'a1', title: 'Item' }]);

    expect(urls).toHaveLength(2);
    const params = new URL(urls[1]!, 'http://localhost').searchParams;
    expect(urls[1]).toContain('api.v1.admin.atstovavimas.meetings.agenda');
    expect(params.get('institution_id')).toBe('inst-1');
    expect(params.getAll('tenant_ids[]')).toEqual(['1', '2']);
  });
});

describe('rows on screen first', () => {
  const rows = Array.from({ length: 60 }, (_, i) => `inst-${String(i).padStart(2, '0')}`);
  const resolvers: Array<() => void> = [];

  beforeEach(() => {
    vi.useFakeTimers();
    fetchedUrls.splice(0);
    resolvers.splice(0);
    vi.stubGlobal('fetch', vi.fn((url: string) => {
      fetchedUrls.push(String(url));
      return new Promise<Response>((resolve) => {
        resolvers.push(() => resolve({ ok: true, json: async () => ({ success: true, data: [] }) } as Response));
      });
    }));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  const idsOf = (url: string) => new URL(url, 'http://localhost').searchParams.getAll('institution_ids[]');

  it('sends nothing for the tenant chart until it reports its rows', async () => {
    const store = useTenantMeetings(() => ['1'], { waitForRows: true });

    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15));
    await vi.advanceTimersByTimeAsync(200);
    expect(fetch).not.toHaveBeenCalled();

    store.setRows(rows, 0, 10);
    await vi.advanceTimersByTimeAsync(200);
    expect(fetch).toHaveBeenCalled();
  });

  it('asks for the batch on screen first, then the batches next to it', async () => {
    const store = useTenantMeetings(() => ['1'], { waitForRows: true });
    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15), { pad: false });
    // Rows 25–35 on screen: the second batch of twenty.
    store.setRows(rows, 25, 35);
    await vi.advanceTimersByTimeAsync(200);

    expect(idsOf(fetchedUrls[0]!)).toEqual(rows.slice(20, 40));
    expect(store.loadingSegments.value[0]!.institutionIds).toEqual(rows.slice(20, 40));

    while (resolvers.length > 0) {
      resolvers.shift()!();
      await vi.advanceTimersByTimeAsync(0);
    }
    const batches = fetchedUrls.map(url => idsOf(url)[0]);
    expect(new Set(batches)).toEqual(new Set(['inst-20', 'inst-00', 'inst-40']));
    expect(store.isFetching.value).toBe(false);
  });

  it('does not ask again for rows already loaded when the view scrolls back', async () => {
    const store = useTenantMeetings(() => ['1'], { waitForRows: true });
    store.ensureRange(new Date(2026, 5, 15), new Date(2026, 6, 15), { pad: false });
    store.setRows(rows, 0, 10);
    await vi.advanceTimersByTimeAsync(200);
    while (resolvers.length > 0) {
      resolvers.shift()!();
      await vi.advanceTimersByTimeAsync(0);
    }
    const requests = fetchedUrls.length;

    store.setRows(rows, 45, 55);
    store.setRows(rows, 0, 10);
    await vi.advanceTimersByTimeAsync(200);

    expect(fetchedUrls).toHaveLength(requests);
  });
});
