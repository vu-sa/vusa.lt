/**
 * useTenantMeetings - Windowed meetings store for the tenant Gantt chart.
 *
 * The Gantt emits the dates on screen as it scrolls or zooms; `ensureRange()` pads them, rounds
 * to whole months and fetches what is missing in chunks of at most six months, nearest the
 * middle of the view first. Meetings are deduped by id.
 */
import { computed, ref, shallowRef, type ComputedRef } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import type { AtstovavimasTenantMeeting, GanttMeeting } from '../types';

import { useToasts } from '@/Composables/useToasts';

/** Smallest padding fetched beyond the visible range; wide views pad a quarter of their span. */
const MIN_BUFFER_DAYS = 30;
const FETCH_DEBOUNCE_MS = 150;
const MS_PER_DAY = 24 * 60 * 60 * 1000;
/** One request covers at most this many months, well under the server's 24-month cap. */
const CHUNK_MONTHS = 6;

interface Window {
  from: number;
  until: number;
}

function monthStart(ts: number): number {
  const d = new Date(ts);
  return new Date(d.getFullYear(), d.getMonth(), 1).getTime();
}

function monthEnd(ts: number): number {
  const d = new Date(ts);
  return new Date(d.getFullYear(), d.getMonth() + 1, 1).getTime() - 1;
}

function chunk(segment: Window): Window[] {
  const chunks: Window[] = [];
  let { from } = segment;
  while (from <= segment.until) {
    const d = new Date(from);
    const until = Math.min(segment.until, new Date(d.getFullYear(), d.getMonth() + CHUNK_MONTHS, 1).getTime() - 1);
    chunks.push({ from, until });
    from = until + 1;
  }
  return chunks;
}

export function useTenantMeetings(tenantIds: () => Array<string | number>) {
  const toasts = useToasts();

  const meetingsById = shallowRef<Map<string, GanttMeeting>>(new Map());
  const loadedWindows = ref<Window[]>([]);
  const isFetching = ref(false);
  /** Union of the windows currently being fetched (for the loading indicator). */
  const pendingWindow = ref<Window | null>(null);
  /** Set when the most recent fetch failed; cleared on the next successful flush. */
  const lastError = ref<string | null>(null);

  let pendingRange: Window | null = null;
  let inFlight = false;
  // Incremented on reset: in-flight responses from a previous tenant set are dropped
  let generation = 0;

  const meetings: ComputedRef<GanttMeeting[]> = computed(() =>
    Array.from(meetingsById.value.values())
      .sort((a, b) => a.start_time.getTime() - b.start_time.getTime()),
  );

  /** Parts of [from, until] not yet covered by loaded windows. */
  function missingSegments(from: number, until: number): Window[] {
    let segments: Window[] = [{ from, until }];

    for (const w of loadedWindows.value) {
      const next: Window[] = [];
      for (const s of segments) {
        if (w.until < s.from || w.from > s.until) {
          next.push(s);
          continue;
        }
        if (w.from > s.from) {
          next.push({ from: s.from, until: Math.min(s.until, w.from - 1) });
        }
        if (w.until < s.until) {
          next.push({ from: Math.max(s.from, w.until + 1), until: s.until });
        }
      }
      segments = next;
    }

    return segments;
  }

  function mergeWindows() {
    const sorted = [...loadedWindows.value].sort((a, b) => a.from - b.from);
    const merged: Window[] = [];

    for (const w of sorted) {
      const last = merged[merged.length - 1];
      // Merge overlapping or adjacent (within a day) windows
      if (last && w.from <= last.until + MS_PER_DAY) {
        last.until = Math.max(last.until, w.until);
      }
      else {
        merged.push({ ...w });
      }
    }

    loadedWindows.value = merged;
  }

  async function fetchWindow(window: Window, refresh = false): Promise<void> {
    const ids = tenantIds().map(String);
    if (ids.length === 0) return;

    const generationAtStart = generation;

    const params = new URLSearchParams();
    ids.forEach(id => params.append('tenant_ids[]', id));
    params.set('from', new Date(window.from).toISOString().slice(0, 10));
    params.set('until', new Date(window.until).toISOString().slice(0, 10));
    if (refresh) params.set('refresh', '1');

    const response = await fetch(`${route('api.v1.admin.visak.meetings')}?${params}`, {
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

    // The tenant selection changed while fetching — drop the stale response
    if (generationAtStart !== generation) return;

    const map = new Map(meetingsById.value);
    for (const meeting of json.data) {
      map.set(String(meeting.id), {
        ...meeting,
        start_time: new Date(meeting.start_time),
      });
    }
    meetingsById.value = map;

    loadedWindows.value = [...loadedWindows.value, window];
    mergeWindows();
  }

  async function flushPendingRange(): Promise<void> {
    if (inFlight || !pendingRange) return;

    const range = pendingRange;
    pendingRange = null;

    const center = (range.from + range.until) / 2;
    const chunks = missingSegments(range.from, range.until)
      .flatMap(chunk)
      .sort((a, b) => Math.abs((a.from + a.until) / 2 - center) - Math.abs((b.from + b.until) / 2 - center));
    if (chunks.length === 0) return;

    inFlight = true;
    isFetching.value = true;
    // Chunks fail independently: a transient error on one must not stop the rest from loading.
    let failures = 0;
    try {
      for (const window of chunks) {
        // The view moved on while fetching; what it shows now matters more than the rest of this range.
        if (pendingRange) break;
        pendingWindow.value = window;
        try {
          await fetchWindow(window);
          lastError.value = null;
        }
        catch (error) {
          failures += 1;
          lastError.value = error instanceof Error ? error.message : String(error);
        }
      }
    }
    finally {
      inFlight = false;
      isFetching.value = false;
      pendingWindow.value = null;

      if (failures > 0) {
        toasts.error($t('visak.gantt.meetings_load_failed'));
      }

      if (pendingRange) {
        void flushPendingRange();
      }
    }
  }

  const debouncedFlush = useDebounceFn(() => {
    void flushPendingRange();
  }, FETCH_DEBOUNCE_MS);

  /** Ensure meetings are loaded for [from, until] plus padding. Debounced; only missing months are fetched. */
  function ensureRange(from: Date, until: Date): void {
    const bufferMs = Math.max(MIN_BUFFER_DAYS * MS_PER_DAY, (until.getTime() - from.getTime()) / 4);
    const window = {
      from: monthStart(from.getTime() - bufferMs),
      until: monthEnd(until.getTime() + bufferMs),
    };

    if (missingSegments(window.from, window.until).length === 0) return;

    // Only the latest view is kept: a union with an earlier, far-away one would fetch everything in between.
    pendingRange = window;
    debouncedFlush();
  }

  /** Re-fetch all currently loaded windows, bypassing the server cache. */
  async function refresh(): Promise<void> {
    debouncedFlush.cancel();
    pendingRange = null;

    isFetching.value = true;
    pendingWindow.value = loadedWindows.value.length > 0
      ? {
          from: Math.min(...loadedWindows.value.map(w => w.from)),
          until: Math.max(...loadedWindows.value.map(w => w.until)),
        }
      : null;
    let failures = 0;
    try {
      for (const window of loadedWindows.value) {
        try {
          await fetchWindow(window, true);
        }
        catch (error) {
          failures += 1;
          lastError.value = error instanceof Error ? error.message : String(error);
        }
      }
    }
    finally {
      isFetching.value = false;
      pendingWindow.value = null;

      if (failures > 0) {
        toasts.error($t('visak.gantt.meetings_load_failed'));
      }
    }
  }

  /** Drop all loaded meetings and windows (e.g. when the tenant selection changes). */
  function reset(): void {
    debouncedFlush.cancel();
    pendingRange = null;
    generation += 1;
    meetingsById.value = new Map();
    loadedWindows.value = [];
  }

  return {
    meetings,
    isFetching,
    pendingWindow,
    lastError,
    ensureRange,
    refresh,
    reset,
  };
}
