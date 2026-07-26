import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref, type Ref } from 'vue';

import { useTenantTimelineData } from '../useTenantTimelineData';
import type { AtstovavimosTenantTimelineData } from '../../types';

const data = ref<AtstovavimosTenantTimelineData | null>(null);
const isFetching = ref(false);
const isSuccess = ref(false);
const pendingRequests: Array<() => void> = [];
let requestUrl: Ref<string>;

const execute = vi.fn(async () => {
  isFetching.value = true;

  await new Promise<void>((resolve) => {
    pendingRequests.push(resolve);
  });

  isSuccess.value = true;
  isFetching.value = false;
});

vi.mock('@/Composables/useApi', () => ({
  useApi: (url: Ref<string>) => {
    requestUrl = url;

    return {
      data,
      isFetching,
      isSuccess,
      execute,
    };
  },
}));

describe('useTenantTimelineData', () => {
  beforeEach(() => {
    data.value = null;
    isFetching.value = false;
    isSuccess.value = false;
    pendingRequests.splice(0);
    execute.mockClear();
  });

  it('queues the latest tenant selection while a request is active', async () => {
    const timeline = useTenantTimelineData();

    const firstLoad = timeline.load(['3']);
    expect(requestUrl.value).toContain('tenant_ids=3');

    await timeline.load(['6']);
    expect(execute).toHaveBeenCalledTimes(1);

    pendingRequests.shift()?.();
    await vi.waitFor(() => {
      expect(execute).toHaveBeenCalledTimes(2);
    });

    expect(requestUrl.value).toContain('tenant_ids=6');

    pendingRequests.shift()?.();
    await firstLoad;
  });

  it('reloads the previous selection when filters change back during a request', async () => {
    const timeline = useTenantTimelineData();

    const initialLoad = timeline.load(['3']);
    pendingRequests.shift()?.();
    await initialLoad;

    const secondLoad = timeline.load(['6']);
    await timeline.load(['3']);

    pendingRequests.shift()?.();
    await vi.waitFor(() => {
      expect(execute).toHaveBeenCalledTimes(3);
    });

    expect(requestUrl.value).toContain('tenant_ids=3');

    pendingRequests.shift()?.();
    await secondLoad;
  });
});
