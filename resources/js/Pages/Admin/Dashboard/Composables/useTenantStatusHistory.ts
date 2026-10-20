import { computed, ref } from 'vue';
import { tryOnScopeDispose } from '@vueuse/core';

import type { InstitutionStatusHistory } from '../types';

import { useApi } from '@/Composables/useApi';

const POLL_BASE_MS = 5000;
const POLL_MAX_MS = 60000;
const POLL_MAX_ATTEMPTS = 8;

export function useTenantStatusHistory() {
  const requestUrl = ref(route('api.v1.admin.visak.timeline.history', {
    tenant_ids: [],
  }));
  const loadedKey = ref('');
  const requestedKey = ref('');

  const {
    data: result,
    error,
    abort,
    isFetching,
    isSuccess,
    execute,
  } = useApi<InstitutionStatusHistory>(requestUrl, {
    immediate: false,
    showErrorToast: false,
  });

  // The api ref keeps the previous scope's payload while the next one is in flight or fails.
  const scoped = computed(() => requestedKey.value === loadedKey.value ? result.value : null);
  const data = computed(() => scoped.value?.points ?? null);
  const pending = computed(() => scoped.value?.pending ?? false);
  const missingDates = computed(() => scoped.value?.missingDates ?? []);
  const baselineDate = computed(() => scoped.value?.baselineDate ?? null);
  const loaded = computed(() => data.value !== null);
  let timer: ReturnType<typeof setTimeout> | null = null;
  let generation = 0;
  let pollAttempts = 0;

  async function load(tenantIds: string[], days: number, force = false, polling = false): Promise<void> {
    const normalizedIds = [...new Set(tenantIds.map(String))].sort();
    const key = `${normalizedIds.join(',')}:${days}`;

    if (normalizedIds.length === 0) {
      return;
    }

    if (!force && key === loadedKey.value && !isFetching.value && !pending.value) {
      return;
    }

    const current = ++generation;
    if (!polling) pollAttempts = 0;
    requestedKey.value = key;
    if (timer) clearTimeout(timer);
    abort();

    requestUrl.value = route('api.v1.admin.visak.timeline.history', {
      tenant_ids: normalizedIds,
      days,
      ...(force ? { refresh: 1 } : {}),
    });

    await execute();

    if (current === generation && isSuccess.value) {
      loadedKey.value = key;
    }
    if (current === generation && pending.value && pollAttempts < POLL_MAX_ATTEMPTS) {
      const delay = Math.min(POLL_BASE_MS * 2 ** pollAttempts, POLL_MAX_MS);
      pollAttempts++;
      timer = setTimeout(() => void load(tenantIds, days, false, true), delay);
    }
  }

  tryOnScopeDispose(() => {
    generation++;
    if (timer) clearTimeout(timer);
    abort();
  });

  return {
    data,
    error,
    pending,
    missingDates,
    baselineDate,
    isFetching,
    loaded,
    load,
  };
}
