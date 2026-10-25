import { useTimeoutFn } from '@vueuse/core';
import { computed, onBeforeUnmount } from 'vue';

import { useApi } from '@/Composables/useApi';

/** Long enough to skip the rows an ↑/↓ run passes over, short enough to feel immediate. */
const SETTLE_MS = 150;

/**
 * Extra data for a preview pane that remounts per selected row: it waits for the selection to
 * settle and aborts when the row changes, so walking a list doesn't queue a request per row.
 */
export function usePreviewApi<T>(url: string, enabled = true) {
  const api = useApi<T>(url, { immediate: false, showErrorToast: false });
  const { stop } = useTimeoutFn(() => api.execute(), SETTLE_MS, { immediate: enabled });

  onBeforeUnmount(() => {
    stop();
    api.abort();
  });

  return {
    data: api.data,
    isLoading: computed(() => enabled && api.data.value === null && !api.error.value),
  };
}
