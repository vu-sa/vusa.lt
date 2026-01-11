import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import PublicBreadcrumbs from '../Public/PublicBreadcrumbs.vue'

// Mock the import.meta.env for development mode
vi.stubGlobal('import', {
  meta: {
    env: {
      DEV: true
    }
  }
})

describe('PublicBreadcrumbs Fallback Mode', () => {
  it('displays warning in development when no breadcrumb state is provided', () => {
    // Spy on console.warn to check if the warning is logged
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})
    
    // Mount component without providing breadcrumb state
    const wrapper = mount(PublicBreadcrumbs)
    
    // Should show the development warning banner
    expect(wrapper.find('[data-testid="fallback-warning"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Breadcrumbs in fallback mode')
    
    // Should have logged the warning
    expect(warnSpy).toHaveBeenCalledWith(
      expect.stringContaining('🍞 [Breadcrumbs] State not provided')
    )
    
    warnSpy.mockRestore()
  })
  
  it('does not break when breadcrumb functions are called in fallback mode', () => {
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})
    
    // This should not throw an error even without state provider
    expect(() => {
      mount(PublicBreadcrumbs)
    }).not.toThrow()
    
    warnSpy.mockRestore()
  })
})
