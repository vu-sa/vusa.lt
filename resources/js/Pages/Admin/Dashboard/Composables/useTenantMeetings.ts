import { computed, getCurrentScope, onScopeDispose, ref, shallowRef, type ComputedRef } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useToasts } from '@/Composables/useToasts';
import { toNaiveDateTimeString } from '@/Utils/dateTime';

import type { GanttAgendaLoader, GanttLoadingSegment } from '@/Components/Graphs/types';

import type { AtstovavimasTenantMeeting, GanttMeeting } from '../types';

/** Smallest padding fetched beyond the visible range; wide views pad a quarter of their span. */
const MIN_BUFFER_DAYS = 30;
const FETCH_DEBOUNCE_MS = 150;
const CONCURRENCY = 3;
const MS_PER_DAY = 24 * 60 * 60 * 1000;
/** One request covers at most this many months, well under the server's 24-month cap. */
const DEFAULT_CHUNK_MONTHS = 6;
const REPRESENTATIVE_ID_LIMIT = 100;

interface Window {
  from: number;
  until: number;
}

interface Task {
  key: string;
  window: Window;
  institutionIds: string[] | null;
  controller: AbortController;
}

/** Chunks sit on calendar boundaries (half-years, years), so a view always asks for the same, cacheable windows. */
function chunkStart(ts: number, months: number): number {
  const d = new Date(ts);
  return new Date(d.getFullYear(), d.getMonth() - (d.getMonth() % months), 1).getTime();
}

function chunkEnd(ts: number, months: number): number {
  const start = new Date(chunkStart(ts, months));
  return new Date(start.getFullYear(), start.getMonth() + months, 1).getTime() - 1;
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

interface WindowedMeetingsSource {
  groups: () => Array<string[] | null>;
  request: (from: string, until: string, refresh: boolean, institutionIds: string[] | null) => { url: string; params: URLSearchParams };
  chunkMonths?: number;
  agendaParams: () => URLSearchParams;
}

type AgendaItems = NonNullable<Awaited<ReturnType<GanttAgendaLoader>>>;

export function useTenantMeetings(tenantIds: () => Array<string | number>) {
  return useWindowedMeetings({
    groups: () => tenantIds().length ? [null] : [],
    request: (from, until, refresh) => {
      const params = new URLSearchParams();
      tenantIds().forEach(id => params.append('tenant_ids[]', String(id)));
      params.set('from', from);
      params.set('until', until);
      if (refresh) params.set('refresh', '1');
      return { url: route('api.v1.admin.visak.meetings'), params };
    },
    agendaParams: () => {
      const params = new URLSearchParams();
      tenantIds().forEach(id => params.append('tenant_ids[]', String(id)));
      return params;
    },
  });
}

export function useInstitutionMeetings(institutionIds: () => Array<string | number>) {
  return useWindowedMeetings({
    groups: () => {
      const ids = [...new Set(institutionIds().map(String))].sort();
      const groups: string[][] = [];
      for (let i = 0; i < ids.length; i += REPRESENTATIVE_ID_LIMIT) {
        groups.push(ids.slice(i, i + REPRESENTATIVE_ID_LIMIT));
      }
      return groups;
    },
    request: (from, until, _refresh, ids) => {
      const params = new URLSearchParams();
      ids?.forEach(id => params.append('institution_ids[]', id));
      params.set('from', from);
      params.set('to', until);
      return { url: route('api.v1.admin.atstovavimas.meetings'), params };
    },
    chunkMonths: 12,
    agendaParams: () => new URLSearchParams(),
  });
}

function useWindowedMeetings(source: WindowedMeetingsSource) {
  const toasts = useToasts();
  const chunkMonths = source.chunkMonths ?? DEFAULT_CHUNK_MONTHS;

  const meetingsById = shallowRef(new Map<string, GanttMeeting>());
  const loaded = new Set<string>();
  const failed = new Set<string>();
  const forceRefresh = new Set<string>();
  const inFlight = shallowRef<Task[]>([]);
  const isFetching = computed(() => inFlight.value.length > 0);
  const lastError = ref<string | null>(null);
  let range: Window | null = null;

  const meetings: ComputedRef<GanttMeeting[]> = computed(() =>
    [...meetingsById.value.values()].sort((a, b) => a.start_time.getTime() - b.start_time.getTime()),
  );
  const loadingSegments = computed<GanttLoadingSegment[]>(() => inFlight.value.map(({ window }) => ({
    from: new Date(window.from),
    until: new Date(window.until),
  })));

  function plan() {
    if (!range) return [];

    const center = (range.from + range.until) / 2;
    const distance = (window: Window) => Math.abs((window.from + window.until) / 2 - center);
    const groups = source.groups();
    return chunk(range, chunkMonths)
      .sort((a, b) => distance(a) - distance(b))
      .flatMap(window => groups.map(institutionIds => ({
        window,
        institutionIds,
        key: `${window.from}:${institutionIds?.join(',') ?? '*'}`,
      })));
  }

  async function fetchWindow(task: Task): Promise<void> {
    const { key, window, controller } = task;
    const { url, params } = source.request(
      toNaiveDateTimeString(new Date(window.from)).slice(0, 10),
      toNaiveDateTimeString(new Date(window.until)).slice(0, 10),
      forceRefresh.has(key),
      task.institutionIds,
    );

    try {
      const response = await fetch(`${url}?${params}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal: controller.signal,
      });
      if (!response.ok) throw new Error(`Failed to fetch meetings window (HTTP ${response.status})`);

      const json = await response.json() as { success?: boolean; data?: AtstovavimasTenantMeeting[] };
      if (!json.success || !Array.isArray(json.data)) throw new Error('Unexpected meetings response shape');
      if (controller.signal.aborted) return;

      const map = new Map(meetingsById.value);
      for (const meeting of json.data) {
        map.set(`${meeting.institution_id}:${meeting.id}`, { ...meeting, start_time: new Date(meeting.start_time) });
      }
      meetingsById.value = map;
      loaded.add(key);
      forceRefresh.delete(key);
    }
    catch (error) {
      if (controller.signal.aborted) return;

      if (!lastError.value) toasts.error($t('visak.gantt.meetings_load_failed'));
      lastError.value = error instanceof Error ? error.message : String(error);
      failed.add(key);
    }
    finally {
      if (!controller.signal.aborted) {
        inFlight.value = inFlight.value.filter(t => t !== task);
        schedule();
      }
    }
  }

  function schedule(): void {
    const activeKeys = new Set(inFlight.value.map(task => task.key));
    const next = plan()
      .filter(({ key }) => !loaded.has(key) && !failed.has(key) && !activeKeys.has(key))
      .slice(0, CONCURRENCY - inFlight.value.length)
      .map(task => ({ ...task, controller: new AbortController() }));
    if (!next.length) return;

    inFlight.value = [...inFlight.value, ...next];
    next.forEach(task => void fetchWindow(task));
  }

  let scheduleTimer: ReturnType<typeof setTimeout> | undefined;
  function resume(): void {
    clearTimeout(scheduleTimer);
    scheduleTimer = setTimeout(schedule, FETCH_DEBOUNCE_MS);
  }

  function ensureRange(from: Date, until: Date, { pad = true }: { pad?: boolean } = {}): void {
    const bufferMs = pad ? Math.max(MIN_BUFFER_DAYS * MS_PER_DAY, (until.getTime() - from.getTime()) / 4) : 0;
    const next = { from: chunkStart(from.getTime() - bufferMs, chunkMonths), until: chunkEnd(until.getTime() + bufferMs, chunkMonths) };
    if (next.from === range?.from && next.until === range.until) return;

    range = next;
    failed.clear();
    lastError.value = null;
    resume();
  }

  function retry(): void {
    failed.clear();
    lastError.value = null;
    schedule();
  }

  function clear(): void {
    clearTimeout(scheduleTimer);
    inFlight.value.forEach(task => task.controller.abort());
    inFlight.value = [];
    meetingsById.value = new Map();
    loaded.clear();
    failed.clear();
    forceRefresh.clear();
    agendas.clear();
    lastError.value = null;
  }

  function refresh(): void {
    clear();
    for (const task of plan()) forceRefresh.add(task.key);
    schedule();
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
        if (agendas.get(key) === request) agendas.delete(key);
        return null;
      });

    agendas.set(key, request);
    return request;
  };

  function reset({ reload = true } = {}): void {
    clear();
    if (reload) resume();
  }

  if (getCurrentScope()) onScopeDispose(clear);

  return { meetings, isFetching, loadingSegments, lastError, ensureRange, refresh, reset, retry, loadAgenda };
}
