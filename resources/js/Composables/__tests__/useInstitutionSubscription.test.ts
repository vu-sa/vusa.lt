import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest';
import { nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useInstitutionSubscription } from '@/Composables/useInstitutionSubscription';

vi.mock('vue-sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}));
const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

describe('useInstitutionSubscription', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage({ csrf_token: 'test-csrf-token' }));
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('toggleFollow should work when following an institution', async () => {
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

    const { toggleFollow } = useInstitutionSubscription();

    const currentState = {
      is_followed: false,
      is_duty_based: false,
    };

    const result = await toggleFollow('test-institution-id', currentState, ['subscription']);

    await nextTick();
    expect(result).toBe(true);
    expect(mockFetch.mock.calls[0][1].method).toBe('POST');
    expect(router.reload).toHaveBeenCalledWith({ only: ['subscription'] });
  });

  test('toggleFollow should work when unfollowing an institution', async () => {
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

    const { toggleFollow } = useInstitutionSubscription();

    const currentState = {
      is_followed: true,
      is_duty_based: false,
    };

    const result = await toggleFollow('test-institution-id', currentState, ['subscription']);

    await nextTick();
    expect(result).toBe(false);
    expect(mockFetch.mock.calls[0][1].method).toBe('DELETE');
    expect(router.reload).toHaveBeenCalledWith({ only: ['subscription'] });
  });

  test.each([false, true])('preserves follow state after a refused request (followed: %s)', async (followed) => {
    const response = { success: false, message: 'Forbidden' };
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 403,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: () => Promise.resolve(response),
      text: () => Promise.resolve(JSON.stringify(response)),
      clone() { return this; },
    });

    const { toggleFollow, isFollowLoading } = useInstitutionSubscription();

    expect(await toggleFollow('test-institution-id', { is_followed: followed, is_duty_based: false }, ['subscription'])).toBe(followed);
    expect(isFollowLoading('test-institution-id')).toBe(false);
    expect(router.reload).not.toHaveBeenCalled();
  });

  test('setFollowedMany sends every id in one request and reloads nothing', async () => {
    const mockResponse = { success: true, data: { institution_ids: ['a', 'b'], is_followed: true } };

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: () => Promise.resolve(mockResponse),
      text: () => Promise.resolve(JSON.stringify(mockResponse)),
      clone() { return this; },
    });

    const { setFollowedMany } = useInstitutionSubscription();

    expect(await setFollowedMany(['a', 'b'], true)).toBe(true);
    expect(mockFetch).toHaveBeenCalledTimes(1);
    expect(JSON.parse(mockFetch.mock.calls[0][1].body)).toEqual({ institution_ids: ['a', 'b'] });
    expect(router.reload).not.toHaveBeenCalled();
  });
});
