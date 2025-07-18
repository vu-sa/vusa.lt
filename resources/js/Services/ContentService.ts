import { usePage } from "@inertiajs/vue3";
import { useFetch } from "@vueuse/core";
import { computed, ref } from "vue";

/**
 * Fetch news for current tenant
 */
export function useNewsFetch() {
  const page = usePage();
  
  const endpoint = route("api.news.tenant.index", {
    lang: page.props.app.locale,
    tenant: page.props.tenant?.alias,
  });
  
  const { data, error, isFetching } = useFetch(endpoint).json();
  
  return {
    news: data,
    loading: isFetching,
    error,
    firstNews: computed(() => data.value?.[0]),
    otherNews: computed(() => data.value ? data.value.slice(1) : []),
  };
}

/**
 * Fetch calendar events for current tenant
 */
export function useCalendarFetch() {
  const page = usePage();
  
  const endpoint = route("api.calendar.tenant.index", {
    lang: page.props.app.locale,
    tenant: page.props.tenant?.alias,
  });

  const { data, error, isFetching } = useFetch(endpoint).json();
  
  // Make sure we're always returning an array even if the API returns null or undefined
  const calendar = computed(() => Array.isArray(data.value) ? data.value : []);

  return {
    calendar,
    loading: isFetching,
    error
  };
}

