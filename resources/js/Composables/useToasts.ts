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
   * Show toast with deduplication and optional custom duration/description
   */
  const showToast = (type: 'success' | 'info' | 'error', message: string, options?: { duration?: number; description?: string }) => {
    // Prevent duplicate toasts within 1 second
    const key = `${type}:${message}`
    if (recentToasts.has(key)) return
    
    recentToasts.add(key)
    
    // Build toast options
    const toastOptions: any = {}
    if (options?.duration) {
      toastOptions.duration = options.duration
    }
    if (options?.description) {
      toastOptions.description = options.description
    }
    
    // Use custom options if provided, otherwise use vue-sonner defaults
    if (Object.keys(toastOptions).length > 0) {
      toast[type](message, toastOptions)
    } else {
      toast[type](message)
    }
    
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
          // Check for custom options
          const options: { duration?: number; description?: string } = {}
          
          if (flash.toast_duration) {
            options.duration = flash.toast_duration
          }
          
          if (flash.toast_description) {
            options.description = flash.toast_description
          }
          
          showToast(type, flash[type], Object.keys(options).length > 0 ? options : undefined)
          flash[type] = null
        }
      })
      
      // Clear toast options after use
      if (flash.toast_duration) {
        flash.toast_duration = null
      }
      if (flash.toast_description) {
        flash.toast_description = null
      }
    }, { deep: true })
  }
  
  /**
   * Watch validation errors
   */
  const watchValidationErrors = () => {
    watch(() => usePage().props.errors, (errors) => {
      if (errors) {
        Object.entries(errors).forEach(([key, value]) => {
          showToast('error', `${key}: ${value}`)
        })
      }
    })
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
    success: (message: string, options?: { duration?: number; description?: string }) => showToast('success', message, options),
    error: (message: string, options?: { duration?: number; description?: string }) => showToast('error', message, options),  
    info: (message: string, options?: { duration?: number; description?: string }) => showToast('info', message, options),
  }
}