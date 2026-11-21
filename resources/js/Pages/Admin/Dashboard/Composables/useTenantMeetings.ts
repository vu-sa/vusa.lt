/**
 * Windowed meetings store for a Gantt chart.
 *
 * The Gantt reports the dates on screen (`ensureRange`) and the institution rows in display order
 * with the slice on screen (`setRows`). Missing (institution batch × month chunk) requests are
 * queued rows-on-screen first, then nearest the middle of the view, and run a few at a time.
 */
import { computed, ref, shallowRef, type ComputedRef } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import { useToasts } from '@/Composables/useToasts';
import { toNaiveDateTimeString } from '@/Utils/dateTime';

import type { GanttAgendaLoader, GanttLoadingSegment } from '@/Components/Graphs/types';

import type { AtstovavimasTenantMeeting, GanttMeeting } from '../types';

/** Smallest padding fetched beyond the visible range; wide views pad a quarter of their span. */
const MIN_BUFFER_DAYS = 30;
const FETCH_DEBOUNCE_MS = 150;
/** Requests in flight at once; the server answers each in tens of milliseconds, so latency dominates. */
const CONCURRENCY = 3;
const MS_PER_DAY = 24 * 60 * 60 * 1000;
/** One request covers at most this many months, well under the server's 24-month cap. */
const DEFAULT_CHUNK_MONTHS = 6;
/** Rows per request, cut along display order so a scroll position always asks for the same, cacheable batches. */
const BATCH_SIZE = 20;
/** Coverage of a request made before the rows were known; it covers every institution. */
const ALL = '*';

interface Window {
  from: number;
  until: number;
}

interface Task {
  window: Window;
  /** null: every institution the source may show (no subset sent). */
  institutionIds: string[] | null;
  /** Bypass the server cache (after an edit). */
  refresh?: boolean;
}


function monthStart(ts: number): number {
  const d = new Date(ts);
  return new Date(d.getFullYear(), d.getMonth(), 1).getTime();
}

function monthEnd(ts: number): number {
  const d = new Date(ts);
  return new Date(d.getFullYear(), d.getMonth() + 1, 1).getTime() - 1;
}

function chunk(segment: Window, months: number): Window[] {
  const chunks: Window[] = [];
  let { from } = segment;
  while (from <= segment.until) {
    const d = new Date(from);
    const until = Math.min(segment.until, new Date(d.getFullYear(), d.getMonth() + months, 1).getTime() - 1);
    chunks.push({ from, until });
    from = until + 1;
  }
  return chunks;
}

/** Parts of `window` not covered by `covered`. */
function subtract(window: Window, covered: Window[]): Window[] {
  let segments: Window[] = [window];
  for (const w of covered) {
    const next: Window[] = [];
    for (const s of segments) {
      if (w.until < s.from || w.from > s.until) {
        next.push(s);
        continue;
      }
      if (w.from > s.from) next.push({ from: s.from, until: Math.min(s.until, w.from - 1) });
      if (w.until < s.until) next.push({ from: Math.max(s.from, w.until + 1), until: s.until });
    }
    segments = next;
  }
  return segments;
}

function merge(windows: Window[]): Window[] {
  const merged: Window[] = [];
  for (const w of [...windows].sort((a, b) => a.from - b.from)) {
    const last = merged[merged.length - 1];
    // Overlapping or adjacent (within a day) windows become one.
    if (last && w.from <= last.until + MS_PER_DAY) {
      last.until = Math.max(last.until, w.until);
    }
    else {
      merged.push({ ...w });
    }
  }
  return merged;
}

interface WindowedMeetingsSource {
  /** Endpoint plus query for one window and (optionally) a subset of institutions, or null when there is nothing to fetch. */
  request: (from: string, until: string, refresh: boolean, institutionIds: string[] | null) => { url: string; params: URLSearchParams } | null;
  keyOf: (meeting: AtstovavimasTenantMeeting) => string;
  chunkMonths?: number;
  /** Query for one meeting's tooltip agenda, resolved with the same access as the window. */
  agendaParams: () => URLSearchParams;
  /** Plan nothing until the chart reports its rows, rather than one request for every institution. */
  waitForRows?: boolean;
}

type AgendaItems = NonNullable<Awaited<ReturnType<GanttAgendaLoader>>>;

export function useTenantMeetings(tenantIds: () => Array<string | number>, { waitForRows = false }: { waitForRows?: boolean } = {}) {
  return useWindowedMeetings({
    waitForRows,
    request: (from, until, refresh, institutionIds) => {
      const ids = tenantIds().map(String);
      if (ids.length === 0) return null;

      const params = new URLSearchParams();
      ids.forEach(id => params.append('tenant_ids[]', id));
      institutionIds?.forEach(id => params.append('institution_ids[]', id));
      params.set('from', from);
      params.set('until', until);
      if (refresh) params.set('refresh', '1');

      return { url: route('api.v1.admin.visak.meetings'), params };
    },
    keyOf: meeting => String(meeting.id),
    agendaParams: () => {
      const params = new URLSearchParams();
      tenantIds().map(String).forEach(id => params.append('tenant_ids[]', id));
      return params;
    },
  });
}

/** The overview's own and related institutions; a joint meeting appears once per institution. */
export function useInstitutionMeetings(institutionIds: () => Array<string | number>) {
  return useWindowedMeetings({
    request: (from, until, _refresh, subset) => {
      const ids = subset ?? institutionIds().map(String);
      if (ids.length === 0) return null;

      const params = new URLSearchParams();
      ids.forEach(id => params.append('institution_ids[]', id));
      params.set('from', from);
      params.set('to', until);

      return { url: route('api.v1.admin.atstovavimas.meetings'), params };
    },
    keyOf: meeting => `${meeting.institution_id}:${meeting.id}`,
    // A handful of institutions: a year is still a small response, and the first view is one request.
    chunkMonths: 12,
    agendaParams: () => new URLSearchParams(),
  });
}

function useWindowedMeetings(source: WindowedMeetingsSource) {
  const toasts = useToasts();
  const chunkMonths = source.chunkMonths ?? DEFAULT_CHUNK_MONTHS;

  const meetingsById = shallowRef<Map<string, GanttMeeting>>(new Map());
  /** Loaded windows per institution id; `ALL` holds requests made without a subset. */
  const coverage = new Map<string, Window[]>();
  // Shallow: tasks are compared by identity, which a deep ref's proxies would break.
  const inFlight = shallowRef<Task[]>([]);
  const isFetching = computed(() => inFlight.value.length > 0);
  /** Set when the most recent fetch failed; cleared on the next success. */
  const lastError = ref<string | null>(null);

  let queue: Task[] = [];
  let range: Window | null = null;
  /** Institution rows in display order, and the slice of them on screen. */
  let rows: string[] = [];
  let visible: { first: number; last: number } = { first: 0, last: -1 };
  // Incremented on reset: responses for a previous selection are dropped.
  let generation = 0;
  let failuresSinceToast = 0;

  const meetings: ComputedRef<GanttMeeting[]> = computed(() =>
    Array.from(meetingsById.value.values())
      .sort((a, b) => a.start_time.getTime() - b.start_time.getTime()),
  );

  const loadingSegments = computed<GanttLoadingSegment[]>(() => inFlight.value.map(task => ({
    from: new Date(task.window.from),
    until: new Date(task.window.until),
    institutionIds: task.institutionIds,
  })));

  function coveredFor(id: string, includeInFlight: boolean): Window[] {
    const windows = [...(coverage.get(id) ?? []), ...(coverage.get(ALL) ?? [])];
    if (includeInFlight) {
      for (const task of inFlight.value) {
        if (task.institutionIds === null || task.institutionIds.includes(id)) windows.push(task.window);
      }
    }
    return windows;
  }

  function markCovered(task: Task): void {
    for (const id of task.institutionIds ?? [ALL]) {
      coverage.set(id, merge([...(coverage.get(id) ?? []), task.window]));
    }
  }

  /** Every missing (batch × chunk), rows on screen first, then nearest the middle of the view. */
  function plan(): Task[] {
    if (!range) return [];

    const chunks = chunk(range, chunkMonths);
    const center = (range.from + range.until) / 2;
    const byDistance = (a: Task, b: Task) =>
      Math.abs((a.window.from + a.window.until) / 2 - center) - Math.abs((b.window.from + b.window.until) / 2 - center);

    if (rows.length === 0) {
      if (source.waitForRows) return [];
      return chunks
        .flatMap(window => subtract(window, coveredFor(ALL, true)))
        .map(window => ({ window, institutionIds: null }))
        .sort(byDistance);
    }

    const batches: string[][] = [];
    for (let i = 0; i < rows.length; i += BATCH_SIZE) batches.push(rows.slice(i, i + BATCH_SIZE));
    const firstOnScreen = Math.floor(visible.first / BATCH_SIZE);
    const lastOnScreen = Math.floor(visible.last / BATCH_SIZE);
    // Batches on screen, then outward from them: the rows a scroll reaches next come before the far end.
    const batchOrder = batches
      .map((_, index) => index)
      .sort((a, b) => {
        const distance = (i: number) => (i < firstOnScreen ? firstOnScreen - i : i > lastOnScreen ? i - lastOnScreen : 0);
        return distance(a) - distance(b) || a - b;
      });

    return batchOrder.flatMap((batchIndex) => {
      const batch = batches[batchIndex]!;
      return chunks
        .filter(window => batch.some(id => subtract(window, coveredFor(id, true)).length > 0))
        .map(window => ({ window, institutionIds: batch }))
        .sort(byDistance);
    });
  }

  async function fetchTask(task: Task, refresh = false): Promise<void> {
    const request = source.request(
      toNaiveDateTimeString(new Date(task.window.from)).slice(0, 10),
      toNaiveDateTimeString(new Date(task.window.until)).slice(0, 10),
      refresh,
      task.institutionIds,
    );
    if (!request) return;

    const generationAtStart = generation;
    const response = await fetch(`${request.url}?${request.params}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch meetings window (HTTP ${response.status})`);
    }

    const json = await response.json() as { success?: boolean; data?: AtstovavimasTenantMeeting[] };
    if (!json?.success || !Array.isArray(json.data)) {
      throw new Error('Unexpected meetings response shape');
    }

    if (generationAtStart !== generation) return;

    const map = new Map(meetingsById.value);
    for (const meeting of json.data) {
      map.set(source.keyOf(meeting), { ...meeting, start_time: new Date(meeting.start_time) });
    }
    meetingsById.value = map;
    markCovered(task);
  }

  async function worker(): Promise<void> {
    while (queue.length > 0) {
      const task = queue.shift()!;
      const generationAtStart = generation;
      inFlight.value = [...inFlight.value, task];
      try {
        await fetchTask(task, task.refresh);
        lastError.value = null;
      }
      catch (error) {
        failuresSinceToast += 1;
        lastError.value = error instanceof Error ? error.message : String(error);
      }
      finally {
        inFlight.value = inFlight.value.filter(t => t !== task);
      }
      if (generationAtStart !== generation) return;
    }

    if (inFlight.value.length === 0 && failuresSinceToast > 0) {
      failuresSinceToast = 0;
      toasts.error($t('visak.gantt.meetings_load_failed'));
    }
  }

  /** Re-plans the queue around what is on screen now; requests already in flight finish. */
  function schedule(): void {
    queue = plan();
    const idle = CONCURRENCY - inFlight.value.length;
    for (let i = 0; i < Math.min(idle, queue.length); i++) void worker();
  }

  const debouncedSchedule = useDebounceFn(schedule, FETCH_DEBOUNCE_MS);

  /**
   * Ensure meetings are loaded for [from, until] plus padding. Debounced; only missing months are fetched.
   * `pad: false` keeps a first load of up to one chunk to one request per batch.
   */
  function ensureRange(from: Date, until: Date, { pad = true }: { pad?: boolean } = {}): void {
    const bufferMs = pad ? Math.max(MIN_BUFFER_DAYS * MS_PER_DAY, (until.getTime() - from.getTime()) / 4) : 0;
    // Re-planned even when unchanged: a failed request is retried on the next report.
    range = { from: monthStart(from.getTime() - bufferMs), until: monthEnd(until.getTime() + bufferMs) };
    void debouncedSchedule();
  }

  /** Institution ids in display order and the indexes of the first and last row on screen. */
  function setRows(orderedIds: Array<string | number>, firstVisible: number, lastVisible: number): void {
    const ids = orderedIds.map(String);
    const sameRows = ids.length === rows.length && ids.every((id, index) => id === rows[index]);
    const sameBatches = Math.floor(firstVisible / BATCH_SIZE) === Math.floor(visible.first / BATCH_SIZE)
      && Math.floor(lastVisible / BATCH_SIZE) === Math.floor(visible.last / BATCH_SIZE);
    visible = { first: firstVisible, last: lastVisible };
    if (sameRows && sameBatches) return;

    rows = ids;
    void debouncedSchedule();
  }

  /** Re-fetch everything loaded so far, bypassing the server cache, in the same chunks and batches. */
  function refresh(): void {
    agendas.clear();
    const byChunk = new Map<string, { window: Window; ids: string[]; all: boolean }>();
    for (const [id, windows] of coverage) {
      for (const window of windows.flatMap(w => chunk(w, chunkMonths))) {
        const key = `${window.from}:${window.until}`;
        const entry = byChunk.get(key) ?? { window, ids: [], all: false };
        if (id === ALL) entry.all = true;
        else entry.ids.push(id);
        byChunk.set(key, entry);
      }
    }
    coverage.clear();

    const tasks: Task[] = [];
    for (const { window, ids, all } of byChunk.values()) {
      if (all) {
        tasks.push({ window, institutionIds: null, refresh: true });
        continue;
      }
      for (let i = 0; i < ids.length; i += BATCH_SIZE) {
        tasks.push({ window, institutionIds: ids.slice(i, i + BATCH_SIZE), refresh: true });
      }
    }

    queue = tasks;
    const idle = CONCURRENCY - inFlight.value.length;
    for (let i = 0; i < Math.min(idle, queue.length); i++) void worker();
  }

  const agendas = new Map<string, Promise<AgendaItems | null>>();

  /** One request per meeting; a failure is not cached, so the next hover retries. */
  const loadAgenda: GanttAgendaLoader = (meeting) => {
    const key = `${meeting.institution_id}:${meeting.id}`;
    const cached = agendas.get(key);
    if (cached) return cached;

    const url = new URL(route('api.v1.admin.atstovavimas.meetings.agenda', { meeting: meeting.id }), window.location.origin);
    source.agendaParams().forEach((value, name) => url.searchParams.append(name, value));
    url.searchParams.set('institution_id', String(meeting.institution_id));
    const request = fetch(url, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
      .then(async (response) => {
        const json = response.ok ? await response.json() as { success?: boolean; data?: { agenda_items?: AgendaItems } } : null;
        if (!json?.success || !Array.isArray(json.data?.agenda_items)) throw new Error('agenda');
        return json.data.agenda_items;
      })
      .catch(() => {
        agendas.delete(key);
        return null;
      });

    agendas.set(key, request);
    return request;
  };

  /** Drop all loaded meetings and coverage (e.g. when the selection changes); keeps the view. */
  function reset(): void {
    debouncedSchedule.cancel?.();
    queue = [];
    generation += 1;
    meetingsById.value = new Map();
    coverage.clear();
    inFlight.value = [];
    agendas.clear();
    rows = [];
    visible = { first: 0, last: -1 };
    const lastRange = range;
    range = null;
    if (lastRange) {
      range = lastRange;
      void debouncedSchedule();
    }
  }

  return {
    meetings,
    isFetching,
    loadingSegments,
    lastError,
    ensureRange,
    setRows,
    refresh,
    reset,
    loadAgenda,
  };
}
