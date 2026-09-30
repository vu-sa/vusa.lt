import { computed, ref, watch, type ComputedRef, type Ref, type WatchSource } from 'vue';
import { useDebounceFn } from '@vueuse/core';

import { useApi } from '@/Composables/useApi';

export interface UseDebouncedQueryOptions<T> {
  /** Return the URL to query, or null/empty string if input requirements (e.g. min length) are not met. */
  url: () => string | null | undefined;
  /** Sources to watch for triggering the debounced lookup. */
  watchSources?: WatchSource[];
  /** Debounce delay in ms. Defaults to 500ms. */
  debounceMs?: number;
  /** Fallback / initial value when no query is active or data has not loaded. */
  initialValue: T;
  /** Whether to show error toasts. Defaults to false for non-blocking advisory queries. */
  showErrorToast?: boolean;
}

export interface UseDebouncedQueryReturn<T> {
  data: ComputedRef<T>;
  isChecking: Ref<boolean>;
  check: () => void;
}

/**
 * Generic live-lookup composable for advisory asynchronous checks (e.g. duplicate user/duty warnings,
 * permalink preview).
 *
 * Encapsulates debouncing, gating execution on non-empty URL conditions, clearing stale results
 * when conditions are no longer met, and exposing reactive status.
 */
export function useDebouncedQuery<T>(options: UseDebouncedQueryOptions<T>): UseDebouncedQueryReturn<T> {
  const url = ref('');

  const { data, isFetching, execute } = useApi<T>(url, {
    immediate: false,
    showErrorToast: options.showErrorToast ?? false,
  });

  const result = computed<T>(() => (url.value ? (data.value ?? options.initialValue) : options.initialValue));

  const run = useDebounceFn(() => {
    const targetUrl = options.url();
    if (!targetUrl) {
      url.value = '';
      return;
    }

    url.value = targetUrl;
    execute();
  }, options.debounceMs ?? 500);

  if (options.watchSources && options.watchSources.length > 0) {
    watch(options.watchSources, run);
  }

  return {
    data: result,
    isChecking: isFetching,
    check: run,
  };
}
