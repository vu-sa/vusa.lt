import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest';
import { nextTick } from 'vue';

import { useApiMutation } from '../useApi';

vi.mock('vue-sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}));
const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

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
});
