import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

/**
 * Unified toast notification composable
 * Handles flash messages, validation errors, and prevents duplicate toasts
 */
export function useToasts() {
  const recentToasts = new Set<string>()
  
  /**
   * Show toast with deduplication
   */
  const showToast = (type: 'success' | 'info' | 'error', message: string) => {
    // Prevent duplicate toasts within 1 second
    const key = `${type}:${message}`
    if (recentToasts.has(key)) return
    
    recentToasts.add(key)
    toast[type](message)
    
    // Clean up after 1 second
    setTimeout(() => recentToasts.delete(key), 1000)
  }
  
  /**
   * Watch flash messages and show toasts
   */
  const watchFlashMessages = () => {
    watch(() => usePage().props.flash, (flash, oldFlash) => {
      const types = ['success', 'info', 'error'] as const
      
      types.forEach(type => {
        if (flash[type] && flash[type] !== oldFlash?.[type]) {
          showToast(type, flash[type])
          flash[type] = null
        }
      })
    }, { deep: true })
  }
  
  /**
   * Watch validation errors (for development)
   */
  const watchValidationErrors = () => {
    if (usePage().props.app.env === 'local') {
      watch(() => usePage().props.errors, (errors) => {
        if (errors) {
          Object.entries(errors).forEach(([key, value]) => {
            showToast('error', `${key}: ${value}`)
          })
        }
      })
    }
  }
  
  /**
   * Initialize all toast watchers
   */
  const initializeToasts = () => {
    watchFlashMessages()
    watchValidationErrors()
  }
  
  return {
    showToast,
    initializeToasts,
    // Direct toast methods for component usage
    success: (message: string) => showToast('success', message),
    error: (message: string) => showToast('error', message),  
    info: (message: string) => showToast('info', message),
  }
}