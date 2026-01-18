import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest'
import { nextTick } from 'vue'

// Mock vue-sonner
vi.mock('vue-sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn()
  }
}))

// Mock fetch globally
const mockFetch = vi.fn()
vi.stubGlobal('fetch', mockFetch)

// Mock route function globally (Ziggy)
vi.stubGlobal('route', (name: string, params?: any) => {
  return `/mocked-route/${name}`
})

// Mock Inertia router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    reload: vi.fn()
  },
  usePage: () => ({
    props: {
      csrf_token: 'test-csrf-token'
    }
  })
}))

import { router } from '@inertiajs/vue3'

// Import after mocks
import { useInstitutionSubscription } from '@/Pages/Admin/Dashboard/Composables/useInstitutionSubscription'

describe('useInstitutionSubscription', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  test('toggleFollow should work when following an institution', async () => {
    const mockResponse = {
      success: true,
      data: {
        is_followed: true,
        is_muted: false,
        message: 'Institution followed'
      }
    }

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: () => Promise.resolve(mockResponse),
      text: () => Promise.resolve(JSON.stringify(mockResponse)),
      clone: function() { return this }
    })

    const { toggleFollow, isFollowLoading } = useInstitutionSubscription()

    const currentState = {
      is_followed: false,
      is_muted: false,
      is_duty_based: false
    }

    console.log('Before toggleFollow...')
    const result = await toggleFollow('test-institution-id', currentState)
    console.log('toggleFollow result:', result)
    console.log('router.reload called:', (router.reload as any).mock.calls)

    await nextTick()

    // Should return the new state (followed = true)
    expect(result).toBe(true)
    expect(router.reload).toHaveBeenCalled()
  })

  test('toggleFollow should work when unfollowing an institution', async () => {
    const mockResponse = {
      success: true,
      data: {
        is_followed: false,
        is_muted: false,
        message: 'Institucija nebestebima'
      }
    }

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: () => Promise.resolve(mockResponse),
      text: () => Promise.resolve(JSON.stringify(mockResponse)),
      clone: function() { return this }
    })

    const { toggleFollow } = useInstitutionSubscription()

    const currentState = {
      is_followed: true,
      is_muted: false,
      is_duty_based: false
    }

    console.log('Before toggleFollow (unfollow)...')
    const result = await toggleFollow('test-institution-id', currentState)
    console.log('toggleFollow (unfollow) result:', result)

    await nextTick()

    // Should return the new state (followed = false)
    expect(result).toBe(false)
    expect(router.reload).toHaveBeenCalled()
  })

  test('toggleMute should work when muting an institution', async () => {
    const mockResponse = {
      success: true,
      data: {
        is_followed: true,
        is_muted: true,
        message: 'Notifications muted'
      }
    }

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: () => Promise.resolve(mockResponse),
      text: () => Promise.resolve(JSON.stringify(mockResponse)),
      clone: function() { return this }
    })

    const { toggleMute } = useInstitutionSubscription()

    const currentState = {
      is_followed: true,
      is_muted: false,
      is_duty_based: false
    }

    console.log('Before toggleMute...')
    const result = await toggleMute('test-institution-id', currentState)
    console.log('toggleMute result:', result)

    await nextTick()

    // Should return the new state (muted = true)
    expect(result).toBe(true)
    expect(router.reload).toHaveBeenCalled()
  })
})
