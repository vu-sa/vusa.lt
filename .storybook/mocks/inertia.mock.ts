import { fn } from 'storybook/test';

// Mock usePage() function returning commonly used page props
export const usePage = fn(() => ({
  props: {
    app: {
      locale: 'lt',
      subdomain: 'www',
      name: 'VU SA'
    },
    auth: {
      user: null
    },
    flash: {
      success: null,
      error: null
    }
  }
}));

// Mock router for Inertia
export const router = {
  visit: fn(),
  get: fn(),
  post: fn(),
  put: fn(),
  patch: fn(),
  delete: fn(),
  reload: fn()
};