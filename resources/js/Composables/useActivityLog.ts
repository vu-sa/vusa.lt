/**
 * Imperative client for the activity-log API (GET api.v1.admin.activityLog.index).
 * Mirrors useDiscussionApi's fetch + CSRF + `{ success, data }` envelope pattern,
 * with cursor-based "load more" state layered on top since the feed is an
 * infinite-scroll list rather than a single fetch-once resource.
 */

import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { ActivityEntry, ActivityLogCursor, ActivityLogFilters } from '@/Types/activityLog';

export function useActivityLog(subjectType: string, subjectId: string) {
  const page = usePage();

  const entries = ref<ActivityEntry[]>([]);
  const loading = ref(false);
  const loadingMore = ref(false);
  const error = ref<string | null>(null);
  const cursor = ref<ActivityLogCursor | null>(null);
  const filters = ref<ActivityLogFilters>({});
  const hasLoadedOnce = ref(false);

  // Every distinct subject.type ever seen for this subject, across all pages
  // and filter combinations -- deliberately never shrinks when `entries` is
  // narrowed by a subject_type filter, or the type Select in
  // ActivityLogSheet.vue would disappear the moment it's used to narrow the
  // feed down to a single type, with no way back to "all types".
  const knownSubjectTypes = ref<Set<string>>(new Set());

  const hasMore = computed(() => cursor.value?.has_more ?? false);
  const availableSubjectTypes = computed(() => Array.from(knownSubjectTypes.value));

  function recordSubjectTypes(list: ActivityEntry[]): void {
    if (list.length === 0) return;
    knownSubjectTypes.value = new Set([...knownSubjectTypes.value, ...list.map((entry) => entry.subject.type)]);
  }

  async function request(cursorParam?: string | null): Promise<{ data: ActivityEntry[]; meta: { cursor: ActivityLogCursor } }> {
    const csrf = (page.props.csrf_token as string | undefined) ?? '';

    const query = new URLSearchParams();
    if (filters.value.scope) query.set('scope', filters.value.scope);
    if (filters.value.event) query.set('event', filters.value.event);
    if (filters.value.subject_type) query.set('subject_type', filters.value.subject_type);
    if (filters.value.causer_id) query.set('causer_id', filters.value.causer_id);
    if (cursorParam) query.set('cursor', cursorParam);

    const url = route('api.v1.admin.activityLog.index', { subjectType, subjectId })
      + (query.toString() ? `?${query.toString()}` : '');

    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
      },
      credentials: 'same-origin',
    });

    const json = await response.json().catch(() => null);

    if (!response.ok || !json?.success) {
      throw new Error(json?.message ?? 'Request failed');
    }

    return json;
  }

  async function load(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const { data, meta } = await request();
      entries.value = data;
      cursor.value = meta.cursor;
      hasLoadedOnce.value = true;
      recordSubjectTypes(data);
    }
    catch (e) {
      error.value = e instanceof Error ? e.message : 'Request failed';
    }
    finally {
      loading.value = false;
    }
  }

  async function loadMore(): Promise<void> {
    if (!hasMore.value || loadingMore.value || !cursor.value?.next) return;

    loadingMore.value = true;
    error.value = null;

    try {
      const { data, meta } = await request(cursor.value.next);
      entries.value = [...entries.value, ...data];
      cursor.value = meta.cursor;
      recordSubjectTypes(data);
    }
    catch (e) {
      error.value = e instanceof Error ? e.message : 'Request failed';
    }
    finally {
      loadingMore.value = false;
    }
  }

  function reset(): void {
    entries.value = [];
    cursor.value = null;
    hasLoadedOnce.value = false;
  }

  function setFilters(next: ActivityLogFilters): void {
    filters.value = next;
    reset();
    void load();
  }

  return {
    entries,
    loading,
    loadingMore,
    error,
    hasMore,
    hasLoadedOnce,
    filters,
    availableSubjectTypes,
    load,
    loadMore,
    reset,
    setFilters,
  };
}
