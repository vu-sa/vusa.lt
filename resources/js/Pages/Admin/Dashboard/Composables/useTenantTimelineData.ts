import { computed, ref } from 'vue';

import type { AtstovavimasTenantTimelineData } from '../types';

import { useApi } from '@/Composables/useApi';

export function useTenantTimelineData() {
  const requestUrl = ref(route('api.v1.admin.visak.timeline', {
    tenant_ids: [],
  }));
  const pendingTenantIds = ref<string[] | null>(null);
  const loadedTenantKey = ref('');

  const {
    data,
    isFetching,
    isSuccess,
    execute,
  } = useApi<AtstovavimasTenantTimelineData>(requestUrl, {
    immediate: false,
  });

  const loaded = computed(() => data.value !== null);

  async function load(tenantIds: string[], force = false): Promise<void> {
    const normalizedIds = [...new Set(tenantIds.map(String))].sort();
    const tenantKey = normalizedIds.join(',');

    if (normalizedIds.length === 0) {
      return;
    }

    if (
      !force
      && tenantKey === loadedTenantKey.value
      && !isFetching.value
      && pendingTenantIds.value === null
    ) {
      return;
    }

    pendingTenantIds.value = normalizedIds;

    if (isFetching.value) {
      return;
    }

    while (pendingTenantIds.value !== null) {
      const idsToLoad = pendingTenantIds.value;
      pendingTenantIds.value = null;
      const requestKey = idsToLoad.join(',');

      requestUrl.value = route('api.v1.admin.visak.timeline', {
        tenant_ids: idsToLoad,
        // Forced reloads (e.g. after creating a meeting) bypass the server cache
        ...(force ? { refresh: 1 } : {}),
      });

      await execute();

      if (isSuccess.value) {
        loadedTenantKey.value = requestKey;
      }
    }
  }

  return {
    data,
    isFetching,
    loaded,
    load,
  };
}
