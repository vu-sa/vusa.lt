import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref, type Ref } from 'vue';

import { useTenantStatusHistory } from '../useTenantStatusHistory';
import type { InstitutionStatusHistoryPoint } from '../../types';

const data = ref<InstitutionStatusHistoryPoint[] | null>(null);
const isFetching = ref(false);
const isSuccess = ref(false);
let requestUrl: Ref<string>;

const execute = vi.fn(async () => {
  isSuccess.value = true;
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

describe('useTenantStatusHistory', () => {
  beforeEach(() => {
    data.value = null;
    isFetching.value = false;
    isSuccess.value = false;
    execute.mockClear();
  });

  it('requests the given tenants and range', async () => {
    const history = useTenantStatusHistory();

    await history.load(['3'], 90);

    expect(requestUrl.value).toContain('tenant_ids=3');
    expect(requestUrl.value).toContain('days=90');
    expect(execute).toHaveBeenCalledTimes(1);
  });

  it('does not refetch for the same tenants and range', async () => {
    const history = useTenantStatusHistory();

    await history.load(['3'], 90);
    await history.load(['3'], 90);

    expect(execute).toHaveBeenCalledTimes(1);
  });

  it('refetches when the range changes even if the tenants stay the same', async () => {
    const history = useTenantStatusHistory();

    await history.load(['3'], 90);
    await history.load(['3'], 180);

    expect(execute).toHaveBeenCalledTimes(2);
    expect(requestUrl.value).toContain('days=180');
  });

  it('refetches when tenants change even if the range stays the same', async () => {
    const history = useTenantStatusHistory();

    await history.load(['3'], 90);
    await history.load(['6'], 90);

    expect(execute).toHaveBeenCalledTimes(2);
  });

  it('bypasses the dedup key when forced', async () => {
    const history = useTenantStatusHistory();

    await history.load(['3'], 90);
    await history.load(['3'], 90, true);

    expect(execute).toHaveBeenCalledTimes(2);
    expect(requestUrl.value).toContain('refresh=1');
  });

  it('does nothing for an empty tenant list', async () => {
    const history = useTenantStatusHistory();

    await history.load([], 90);

    expect(execute).not.toHaveBeenCalled();
  });
});
