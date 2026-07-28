/**
 * useTenantMeetings - Windowed meetings store for the tenant Gantt chart.
 *
 * The tenant Gantt can scroll infinitely through time, so meetings are loaded
 * in date windows (quantized to month boundaries for cache friendliness)
 * instead of all at once. The Gantt emits `range-changed` when its timeline
 * range extends; `ensureRange()` fetches only the missing month segments and
 * dedupes meetings by id.
 *
 * Meetings are fetched silently in the background; `isFetching` is exposed for
 * a subtle loading indicator (consumers should delay it ~300ms to avoid flashes).
 */
import { computed, ref, shallowRef, type ComputedRef } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useToasts } from '@/Composables/useToasts';

import type { AtstovavimasTenantMeeting, GanttMeeting } from '../types';

/** Extra days fetched beyond the visible range so scrolling feels instant. */
const BUFFER_DAYS = 30;
const FETCH_DEBOUNCE_MS = 150;
const MS_PER_DAY = 24 * 60 * 60 * 1000;
/**
 * Defensive cap on a single requested span (the server caps at 24 months).
 * The visible range is normally a few months; this only kicks in if a caller
 * asks for an extreme span (e.g. a timeline extended years via infinite scroll).
 */
const MAX_SPAN_DAYS = 540;

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

/**
 * Defensive clamp for extreme spans (e.g. a timeline extended years back, or an
 * accumulated `pendingRange` union of several far-apart requests) — the server
 * caps a single window at 24 months, and a wider request would 422 forever
 * without this, since the failing segment never gets marked as loaded.
 */
function clampSpan(from: number, until: number): Window {
  if (until - from <= MAX_SPAN_DAYS * MS_PER_DAY) {
    return { from, until };
  }

  const mid = (from + until) / 2;
  return {
    from: monthStart(mid - (MAX_SPAN_DAYS / 2) * MS_PER_DAY),
    until: monthEnd(mid + (MAX_SPAN_DAYS / 2) * MS_PER_DAY),
  };
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
  let debounceTimer: ReturnType<typeof setTimeout> | null = null;
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

    const segments = missingSegments(range.from, range.until);
    if (segments.length === 0) return;

    inFlight = true;
    isFetching.value = true;
    pendingWindow.value = {
      from: Math.min(...segments.map(s => s.from)),
      until: Math.max(...segments.map(s => s.until)),
    };
    // Segments are tried independently: one failing window (e.g. a transient network
    // error) must not stop the others from loading.
    let failures = 0;
    try {
      for (const segment of segments) {
        try {
          await fetchWindow(segment);
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

      // A range may have been requested while fetching
      if (pendingRange) {
        void flushPendingRange();
      }
    }
  }

  /**
   * Ensure meetings are loaded for [from, until] (plus a buffer on each side).
   * Debounced; fetches only the missing month-quantized segments.
   */
  function ensureRange(from: Date, until: Date): void {
    const { from: fromTs, until: untilTs } = clampSpan(
      monthStart(from.getTime() - BUFFER_DAYS * MS_PER_DAY),
      monthEnd(until.getTime() + BUFFER_DAYS * MS_PER_DAY),
    );

    if (missingSegments(fromTs, untilTs).length === 0) return;

    // Re-clamp after merging with any still-pending range: two individually-clamped
    // ranges can still union into something wider than the server allows.
    pendingRange = clampSpan(
      pendingRange ? Math.min(pendingRange.from, fromTs) : fromTs,
      pendingRange ? Math.max(pendingRange.until, untilTs) : untilTs,
    );

    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      void flushPendingRange();
    }, FETCH_DEBOUNCE_MS);
  }

  /** Re-fetch all currently loaded windows, bypassing the server cache. */
  async function refresh(): Promise<void> {
    if (debounceTimer) clearTimeout(debounceTimer);
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
    if (debounceTimer) clearTimeout(debounceTimer);
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
