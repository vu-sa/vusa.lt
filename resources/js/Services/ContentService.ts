import { usePage } from "@inertiajs/vue3";
import { useFetch } from "@vueuse/core";
import { computed, ref } from "vue";
import { format, subDays, addDays } from "date-fns";
import type { NewsItem } from "@/Types/contentParts";

interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  meta?: {
    date_from?: string;
    date_to?: string;
    max_range_days?: number;
  };
}

interface CalendarEvent {
  id: number;
  title: string;
  date: string;
  category: { id: number; name: string } | null;
  images: Array<{ url: string }>;
  [key: string]: unknown;
}

/**
 * Fetch news for current tenant
 */
export function useNewsFetch() {
  const page = usePage();
  
  const endpoint = route("api.v1.tenants.news.index", {
    tenant: page.props.tenant?.alias,
  }) + `?lang=${page.props.app.locale}`;
  
  const { data, error, isFetching } = useFetch(endpoint).json<ApiResponse<NewsItem[]>>();
  
  // Extract data from standardized API response
  const newsItems = computed(() => data.value?.success ? data.value.data : []);
  
  return {
    news: newsItems,
    loading: isFetching,
    error,
    firstNews: computed(() => newsItems.value?.[0]),
    otherNews: computed(() => newsItems.value?.slice(1) ?? []),
  };
}

/**
 * Default timeline configuration (matches EventTimeline component defaults)
 * 
 * FETCH_CHUNK_DAYS: How many days to fetch at once (larger = fewer requests)
 * BUFFER_DAYS: Trigger fetch when view is within this many days of loaded boundary
 * INITIAL_PAST_DAYS: How far back to fetch initially (365 = 1 year to capture seasonal events like freshmen camps)
 */
const TIMELINE_DEFAULTS = {
  DAYS_PAST: 7,
  DAYS_FUTURE: 21,
  LOAD_MORE_DAYS: 14,
  MAX_RANGE_DAYS: 90,
  FETCH_CHUNK_DAYS: 180, // Fetch 6 months at a time to reduce API calls
  BUFFER_DAYS: 30, // Fetch more when within 30 days of boundary
  INITIAL_PAST_DAYS: 365, // Fetch 1 year of past events to include freshmen camps etc.
  INITIAL_FUTURE_DAYS: 90, // Fetch 90 days of future events
};

/**
 * Fetch calendar events with date-based loading for the EventTimeline component.
 * 
 * Uses smart fetching to minimize API calls:
 * - Fetches large chunks (6 months) at a time
 * - Only fetches when view approaches loaded boundaries
 * - Tracks loaded date range to avoid redundant requests
 * 
 * @param options.allTenants - Whether to fetch events from all tenants
 * @param options.skipInitialFetch - Skip initial API fetch (use when prefetched data is available)
 */
export function useCalendarFetch(options?: { allTenants?: boolean; skipInitialFetch?: boolean }) {
  const page = usePage();
  
  // Track the full loaded date range (boundaries of all loaded data)
  const today = new Date();
  const loadedFromDate = ref<Date>(subDays(today, TIMELINE_DEFAULTS.DAYS_PAST));
  const loadedToDate = ref<Date>(addDays(today, TIMELINE_DEFAULTS.DAYS_FUTURE));
  
  // Current view date range (what the user is looking at)
  const viewFromDate = ref<Date>(subDays(today, TIMELINE_DEFAULTS.DAYS_PAST));
  const viewToDate = ref<Date>(addDays(today, TIMELINE_DEFAULTS.DAYS_FUTURE));
  
  // All accumulated events (deduplicated)
  const allEvents = ref<CalendarEvent[]>([]);
  
  // Loading states
  const loading = ref(false);
  const loadingPast = ref(false);
  const loadingFuture = ref(false);
  const error = ref<Error | null>(null);
  
  // Metadata from API
  const maxRangeDays = ref(TIMELINE_DEFAULTS.MAX_RANGE_DAYS);
  
  /**
   * Build API endpoint URL with date range parameters
   */
  const buildEndpoint = (from: Date, to: Date): string => {
    const queryParams = new URLSearchParams({
      lang: page.props.app.locale as string,
      date_from: format(from, 'yyyy-MM-dd'),
      date_to: format(to, 'yyyy-MM-dd'),
    });
    
    if (options?.allTenants) {
      queryParams.set('all_tenants', 'true');
    }
    
    return route("api.v1.tenants.calendar.index", {
      tenant: page.props.tenant?.alias,
    }) + `?${queryParams.toString()}`;
  };
  
  /**
   * Merge new events with existing ones, deduplicating by ID
   */
  const mergeEvents = (newEvents: CalendarEvent[]): void => {
    const existingIds = new Set(allEvents.value.map(e => e.id));
    const uniqueNewEvents = newEvents.filter(e => !existingIds.has(e.id));
    
    allEvents.value = [...allEvents.value, ...uniqueNewEvents]
      .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
  };
  
  /**
   * Fetch events for a specific date range
   */
  const fetchRange = async (from: Date, to: Date): Promise<void> => {
    const endpoint = buildEndpoint(from, to);
    
    try {
      const response = await fetch(endpoint);
      
      if (response.status === 429) {
        // Rate limited - don't throw, just log and skip
        console.warn('Calendar API rate limited, will retry on next navigation');
        return;
      }
      
      const result = await response.json() as ApiResponse<CalendarEvent[]>;
      
      if (result.success && Array.isArray(result.data)) {
        mergeEvents(result.data);
        
        // Update max range from API response
        if (result.meta?.max_range_days) {
          maxRangeDays.value = result.meta.max_range_days;
        }
      }
    } catch (e) {
      error.value = e instanceof Error ? e : new Error('Failed to fetch calendar events');
      console.error('Calendar fetch error:', e);
    }
  };
  
  /**
   * Initial fetch - load a generous initial range
   * Fetches 1 year past (to include seasonal events like freshmen camps) and 90 days future
   */
  const fetchInitial = async (): Promise<void> => {
    loading.value = true;
    error.value = null;
    
    try {
      // Fetch a larger initial range to include seasonal events (freshmen camps in August, etc.)
      const initialFrom = subDays(today, TIMELINE_DEFAULTS.INITIAL_PAST_DAYS);
      const initialTo = addDays(today, TIMELINE_DEFAULTS.INITIAL_FUTURE_DAYS);
      
      await fetchRange(initialFrom, initialTo);
      
      // Update loaded boundaries
      loadedFromDate.value = initialFrom;
      loadedToDate.value = initialTo;
    } finally {
      loading.value = false;
    }
  };
  
  /**
   * Check if we need to fetch more past events based on current view
   */
  const needsFetchPast = (): boolean => {
    const bufferDate = addDays(loadedFromDate.value, TIMELINE_DEFAULTS.BUFFER_DAYS);
    return viewFromDate.value < bufferDate;
  };
  
  /**
   * Check if we need to fetch more future events based on current view
   */
  const needsFetchFuture = (): boolean => {
    const bufferDate = subDays(loadedToDate.value, TIMELINE_DEFAULTS.BUFFER_DAYS);
    return viewToDate.value > bufferDate;
  };
  
  /**
   * Fetch more events in the past direction (called when view approaches boundary)
   */
  const fetchPast = async (): Promise<void> => {
    // Update view range
    viewFromDate.value = subDays(viewFromDate.value, TIMELINE_DEFAULTS.LOAD_MORE_DAYS);
    
    // Only actually fetch if we're approaching the loaded boundary
    if (!needsFetchPast()) {
      return;
    }
    
    loadingPast.value = true;
    
    try {
      const newFrom = subDays(loadedFromDate.value, TIMELINE_DEFAULTS.FETCH_CHUNK_DAYS);
      const newTo = subDays(loadedFromDate.value, 1);
      
      await fetchRange(newFrom, newTo);
      loadedFromDate.value = newFrom;
    } finally {
      loadingPast.value = false;
    }
  };
  
  /**
   * Fetch more events in the future direction (called when view approaches boundary)
   */
  const fetchFuture = async (): Promise<void> => {
    // Update view range
    viewToDate.value = addDays(viewToDate.value, TIMELINE_DEFAULTS.LOAD_MORE_DAYS);
    
    // Only actually fetch if we're approaching the loaded boundary
    if (!needsFetchFuture()) {
      return;
    }
    
    loadingFuture.value = true;
    
    try {
      const newFrom = addDays(loadedToDate.value, 1);
      const newTo = addDays(loadedToDate.value, TIMELINE_DEFAULTS.FETCH_CHUNK_DAYS);
      
      await fetchRange(newFrom, newTo);
      loadedToDate.value = newTo;
    } finally {
      loadingFuture.value = false;
    }
  };
  
  /**
   * Refresh all data (clear cache and refetch)
   */
  const refresh = async (): Promise<void> => {
    allEvents.value = [];
    viewFromDate.value = subDays(today, TIMELINE_DEFAULTS.DAYS_PAST);
    viewToDate.value = addDays(today, TIMELINE_DEFAULTS.DAYS_FUTURE);
    await fetchInitial();
  };
  
  /**
   * Initialize with prefetched data from server (for LCP optimization)
   * This allows skipping the initial API call when data is already available
   */
  const initializeWithData = (events: CalendarEvent[]): void => {
    allEvents.value = events;
    // Mark as loaded with a generous range since server data covers the initial view
    loadedFromDate.value = subDays(today, TIMELINE_DEFAULTS.INITIAL_PAST_DAYS);
    loadedToDate.value = addDays(today, TIMELINE_DEFAULTS.INITIAL_FUTURE_DAYS);
  };
  
  // Execute initial fetch only if not skipping
  if (!options?.skipInitialFetch) {
    fetchInitial();
  }

  return {
    // Events
    calendar: allEvents,
    
    // Current loaded date range (for debugging/display)
    loadedFromDate,
    loadedToDate,
    
    // Loading states
    loading,
    loadingPast,
    loadingFuture,
    error,
    
    // Actions
    fetchPast,
    fetchFuture,
    refresh,
    initializeWithData,
    
    // Configuration
    maxRangeDays,
    defaults: TIMELINE_DEFAULTS,
  };
}

