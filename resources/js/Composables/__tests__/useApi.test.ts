import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest'
import { nextTick, ref } from 'vue'

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

// Import after mocks
import { useApiMutation } from '../useApi'

describe('useApiMutation', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  test('should correctly parse successful JSON response with success: true', async () => {
    // Mock the API response exactly as the backend returns it
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

    const { execute, isSuccess, data, isFetching, isFinished } = useApiMutation<{
      is_followed: boolean
      is_muted: boolean
      message: string
    }>(
      '/api/v1/admin/institutions/test-id/follow',
      'POST',
      undefined,
      { showSuccessToast: false, showErrorToast: false }
    )

    // Execute the request
    await execute()

    // Wait for Vue reactivity
    await nextTick()

    // Log values for debugging
    console.log('isFetching:', isFetching.value)
    console.log('isFinished:', isFinished.value)
    console.log('isSuccess:', isSuccess.value)
    console.log('data:', data.value)

    // Verify the response was parsed correctly
    expect(isSuccess.value).toBe(true)
    expect(data.value).toEqual({
      is_followed: true,
      is_muted: false,
      message: 'Institution followed'
    })
  })

  test('should handle unfollow (DELETE) request correctly', async () => {
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

    const { execute, isSuccess, data } = useApiMutation<{
      is_followed: boolean
      is_muted: boolean
      message: string
    }>(
      '/api/v1/admin/institutions/test-id/unfollow',
      'DELETE',
      undefined,
      { showSuccessToast: false, showErrorToast: false }
    )

    await execute()
    await nextTick()

    console.log('DELETE isSuccess:', isSuccess.value)
    console.log('DELETE data:', data.value)

    expect(isSuccess.value).toBe(true)
    expect(data.value?.is_followed).toBe(false)
  })
})
