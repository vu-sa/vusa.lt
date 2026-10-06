import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest';
import { nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useInstitutionSubscription } from '@/Composables/useInstitutionSubscription';

// Mock vue-sonner
vi.mock('vue-sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}));

// Mock fetch globally
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
        is_muted: false,
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

    const { toggleFollow, isFollowLoading } = useInstitutionSubscription();

    const currentState = {
      is_followed: false,
      is_muted: false,
      is_duty_based: false,
    };

    const result = await toggleFollow('test-institution-id', currentState, ['subscription']);

    await nextTick();

    // Should return the new state (followed = true)
    expect(result).toBe(true);
    expect(router.reload).toHaveBeenCalled();
  });

  test('toggleFollow should work when unfollowing an institution', async () => {
    const mockResponse = {
      success: true,
      data: {
        is_followed: false,
        is_muted: false,
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
      is_muted: false,
      is_duty_based: false,
    };

    const result = await toggleFollow('test-institution-id', currentState, ['subscription']);

    await nextTick();

    // Should return the new state (followed = false)
    expect(result).toBe(false);
    expect(router.reload).toHaveBeenCalled();
  });

  test('toggleMute should work when muting an institution', async () => {
    const mockResponse = {
      success: true,
      data: {
        is_followed: true,
        is_muted: true,
        message: 'Notifications muted',
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

    const { toggleMute } = useInstitutionSubscription();

    const currentState = {
      is_followed: true,
      is_muted: false,
      is_duty_based: false,
    };

    const result = await toggleMute('test-institution-id', currentState, ['subscription']);

    await nextTick();

    // Should return the new state (muted = true)
    expect(result).toBe(true);
    expect(router.reload).toHaveBeenCalled();
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
