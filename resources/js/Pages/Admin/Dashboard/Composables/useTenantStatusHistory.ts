import { computed, ref } from 'vue';

import type { InstitutionStatusHistoryPoint } from '../types';

import { useApi } from '@/Composables/useApi';

export function useTenantStatusHistory() {
  const requestUrl = ref(route('api.v1.admin.visak.timeline.history', {
    tenant_ids: [],
  }));
  const loadedKey = ref('');

  const {
    data,
    isFetching,
    isSuccess,
    execute,
  } = useApi<InstitutionStatusHistoryPoint[]>(requestUrl, {
    immediate: false,
  });

  const loaded = computed(() => data.value !== null);

  async function load(tenantIds: string[], days: number, force = false): Promise<void> {
    const normalizedIds = [...new Set(tenantIds.map(String))].sort();
    const key = `${normalizedIds.join(',')}:${days}`;

    if (normalizedIds.length === 0) {
      return;
    }

    if (!force && key === loadedKey.value && !isFetching.value) {
      return;
    }

    requestUrl.value = route('api.v1.admin.visak.timeline.history', {
      tenant_ids: normalizedIds,
      days,
      ...(force ? { refresh: 1 } : {}),
    });

    await execute();

    if (isSuccess.value) {
      loadedKey.value = key;
    }
  }

  return {
    data,
    isFetching,
    loaded,
    load,
  };
}
