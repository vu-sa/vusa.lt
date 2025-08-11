import { describe, test, expect, beforeEach, afterEach, vi } from 'vitest'
import { nextTick, reactive } from 'vue'

// Mock vue-sonner first
vi.mock('vue-sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn()
  }
}))

import { toast } from 'vue-sonner'
const mockToast = vi.mocked(toast)

// Simple reactive mock - just like Inertia would provide
let mockPageProps = reactive({
  flash: {
    success: null,
    error: null,
    info: null
  },
  app: {
    env: 'local'
  },
  errors: {}
})

// Mock Inertia usePage - keep it simple
vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({ props: mockPageProps })
}))

// Import after mocks are set up
import { useToasts } from '../useToasts'

describe('useToasts', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset flash messages and errors
    mockPageProps.flash.success = null
    mockPageProps.flash.error = null
    mockPageProps.flash.info = null
    mockPageProps.errors = {}
    mockPageProps.app.env = 'local'
  })

  afterEach(() => {
    vi.clearAllTimers()
  })

  describe('showToast functionality', () => {
    test('shows toasts and prevents duplicates within 1 second', async () => {
      vi.useFakeTimers()
      const { showToast } = useToasts()
      
      // Test each toast type
      showToast('success', 'Success message')
      showToast('error', 'Error message') 
      showToast('info', 'Info message')
      
      expect(mockToast.success).toHaveBeenCalledWith('Success message')
      expect(mockToast.error).toHaveBeenCalledWith('Error message')
      expect(mockToast.info).toHaveBeenCalledWith('Info message')
      
      // Test deduplication
      showToast('success', 'Duplicate message')
      showToast('success', 'Duplicate message')
      expect(mockToast.success).toHaveBeenCalledTimes(2) // 1 from above + 1 new
      
      // After timeout, allows duplicate
      vi.advanceTimersByTime(1000)
      showToast('success', 'Duplicate message')
      expect(mockToast.success).toHaveBeenCalledTimes(3)
      
      vi.useRealTimers()
    })
  })

  describe('direct toast methods', () => {
    test('success/error/info methods call showToast', () => {
      const { success, error, info } = useToasts()
      
      success('Success message')
      error('Error message')
      info('Info message')
      
      expect(mockToast.success).toHaveBeenCalledWith('Success message')
      expect(mockToast.error).toHaveBeenCalledWith('Error message')
      expect(mockToast.info).toHaveBeenCalledWith('Info message')
    })
  })

  describe('flash message watching', () => {
    test('displays flash messages and nullifies them', async () => {
      const { initializeToasts } = useToasts()
      initializeToasts()
      await nextTick()
      
      // Test success flash
      mockPageProps.flash = { success: 'Flash success', error: null, info: null }
      await nextTick()
      expect(mockToast.success).toHaveBeenCalledWith('Flash success')
      expect(mockPageProps.flash.success).toBeNull()
      
      // Test error flash  
      mockPageProps.flash = { success: null, error: 'Flash error', info: null }
      await nextTick()
      expect(mockToast.error).toHaveBeenCalledWith('Flash error')
      expect(mockPageProps.flash.error).toBeNull()
      
      // Test info flash
      mockPageProps.flash = { success: null, error: null, info: 'Flash info' }
      await nextTick()
      expect(mockToast.info).toHaveBeenCalledWith('Flash info')
      expect(mockPageProps.flash.info).toBeNull()
    })
  })

  describe('validation error watching', () => {
    test('shows validation errors in local environment', async () => {
      const { initializeToasts } = useToasts()
      
      mockPageProps.app.env = 'local'
      initializeToasts()
      await nextTick()
      vi.clearAllMocks()
      
      mockPageProps.errors = { email: 'Required', password: 'Too short' }
      await nextTick()
      
      expect(mockToast.error).toHaveBeenCalledWith('email: Required')
      expect(mockToast.error).toHaveBeenCalledWith('password: Too short')
    })
  })
})