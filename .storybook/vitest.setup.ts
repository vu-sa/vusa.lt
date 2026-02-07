import * as a11yAddonAnnotations from "@storybook/addon-a11y/preview";
import { beforeAll, beforeEach, afterEach } from 'vitest'
import { setProjectAnnotations } from '@storybook/vue3-vite'

import * as projectAnnotations from './preview'

// Apply Storybook's project annotations to Vitest tests
const project = setProjectAnnotations([a11yAddonAnnotations, projectAnnotations])

// Apply project-level setup
beforeAll(project.beforeAll)

// Apply per-test setup and cleanup
beforeEach(() => {
  if (typeof project.beforeEach === 'function') {
    return project.beforeEach()
  }
})

afterEach(() => {
  if (typeof project.afterEach === 'function') {
    return project.afterEach()
  }
})

// Essential browser API mocks for testing environment
beforeAll(() => {
  // Mock matchMedia for responsive components
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: (query: string) => ({
      matches: false,
      media: query,
      onchange: null,
      addListener: () => { },
      removeListener: () => { },
      addEventListener: () => { },
      removeEventListener: () => { },
      dispatchEvent: () => { },
    }),
  })

  // Mock ResizeObserver
  globalThis.ResizeObserver = class ResizeObserver {
    observe() { }
    unobserve() { }
    disconnect() { }
  }
})
