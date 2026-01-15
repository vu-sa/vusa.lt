import { usePage } from "@inertiajs/vue3";
import { useFetch } from "@vueuse/core";
import { computed, ref } from "vue";

interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

interface NewsItem {
  id: number;
  title: string;
  lang: string;
  short: string;
  publish_time: string;
  permalink: string;
  image: string | null;
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
 * Fetch calendar events for current tenant
 */
export function useCalendarFetch(options?: { allTenants?: boolean }) {
  const page = usePage();
  
  const queryParams = new URLSearchParams({
    lang: page.props.app.locale as string,
  });
  
  if (options?.allTenants) {
    queryParams.set('all_tenants', 'true');
  }
  
  const endpoint = route("api.v1.tenants.calendar.index", {
    tenant: page.props.tenant?.alias,
  }) + `?${queryParams.toString()}`;

  const { data, error, isFetching, execute } = useFetch(endpoint, { immediate: true }).json<ApiResponse<CalendarEvent[]>>();
  
  // Extract data from standardized API response
  const calendarEvents = computed(() => {
    if (data.value?.success && Array.isArray(data.value.data)) {
      return data.value.data;
    }
    return [];
  });

  return {
    calendar: calendarEvents,
    loading: isFetching,
    error,
    refresh: execute,
  };
}

