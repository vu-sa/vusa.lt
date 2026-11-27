import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest';
import { nextTick } from 'vue';
import { toast } from 'vue-sonner';

import { scopedGlobals } from '@/tests/helpers/scopedGlobals';

import { useApiMutation } from '../useApi';

const { stubGlobal, restoreGlobals } = scopedGlobals();
afterEach(restoreGlobals);

vi.mock('vue-sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}));
const mockFetch = vi.fn();
beforeEach(() => {
  stubGlobal('fetch', mockFetch);
});

describe('useApiMutation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('should correctly parse successful JSON response with success: true', async () => {
    const mockResponse = {
      success: true,
      data: {
        is_followed: true,
        message: 'Institution followed',
      },
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: () => Promise.resolve(mockResponse),
      text: () => Promise.resolve(JSON.stringify(mockResponse)),
      clone() { return this; },
    });

    const { execute, isSuccess, data } = useApiMutation<{
      is_followed: boolean;
      message: string;
    }>(
      '/api/v1/admin/institutions/test-id/follow',
      'POST',
      undefined,
      { showSuccessToast: false, showErrorToast: false },
    );
    await execute();
    await nextTick();
    expect(isSuccess.value).toBe(true);
    expect(data.value).toEqual({
      is_followed: true,
      message: 'Institution followed',
    });
  });

  test('should handle unfollow (DELETE) request correctly', async () => {
    const mockResponse = {
      success: true,
      data: {
        is_followed: false,
        message: 'Institucija nebestebima',
      },
    };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: () => Promise.resolve(mockResponse),
      text: () => Promise.resolve(JSON.stringify(mockResponse)),
      clone() { return this; },
    });

    const { execute, isSuccess, data } = useApiMutation<{
      is_followed: boolean;
      message: string;
    }>(
      '/api/v1/admin/institutions/test-id/unfollow',
      'DELETE',
      undefined,
      { showSuccessToast: false, showErrorToast: false },
    );

    await execute();
    await nextTick();

    expect(isSuccess.value).toBe(true);
    expect(data.value?.is_followed).toBe(false);
  });

  test('shows only the session-expired toast on 419', async () => {
    const body = { message: 'CSRF token mismatch.' };
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 419,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: () => Promise.resolve(body),
      text: () => Promise.resolve(JSON.stringify(body)),
      clone() { return this; },
    });

    const { execute } = useApiMutation('/api/v1/admin/institutions/test-id/follow', 'POST');
    await execute();

    expect(toast.error).toHaveBeenCalledOnce();
    expect(vi.mocked(toast.error).mock.calls[0]?.[1]).toMatchObject({ id: 'session-expired' });
  });
});
