import { vi } from 'vitest';

// Mock icons for tests
vi.mock('~icons/fluent/home24-filled', () => ({
  default: {},
}));

// Mock route function from Ziggy
vi.mock('ziggy-js', () => ({
  default: (name: string, params: any) => {
    return `/mocked-route/${name}`;
  },
  route: (name: string, params: any) => {
    return `/mocked-route/${name}`;
  },
}));

// Mock InertiaJS
vi.mock('@inertiajs/vue3', () => ({
  Link: vi.fn(),
  Head: vi.fn(),
  usePage: vi.fn(() => ({
    props: {
      app: {
        locale: 'lt',
      },
      auth: {
        user: {
          id: 1,
          name: 'Test User',
        },
      },
      tenants: [],
      flash: {},
    },
  })),
  router: {
    reload: vi.fn(),
    visit: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
  useForm: vi.fn((defaults) => ({
    ...defaults,
    errors: {},
    hasErrors: false,
    processing: false,
    isDirty: false,
    reset: vi.fn(),
    setError: vi.fn(),
    clearErrors: vi.fn(),
    submit: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  })),
}));

// Helper function for translations - used by both imports and template globals
const translateFn = (key: string, params?: any) => key;

// Mock i18n functions
vi.mock('laravel-vue-i18n', () => ({
  trans: translateFn,
  transChoice: vi.fn((key: string, count: number, params?: any) => key),
  createI18nInstance: vi.fn(),
  loadLanguageAsync: vi.fn(),
}));

// Add global properties to Vue component instances for testing
import { config } from '@vue/test-utils';

config.global.mocks = {
  // Mock $t template global property
  $t: translateFn,
  // Mock $tChoice template global property
  $tChoice: (key: string, count: number, params?: any) => key,
  // Add route helper as a global property if used in templates
  route: (name: string, params: any) => `/mocked-route/${name}`,
  // Add $page if needed in more components
  $page: {
    props: {
      app: {
        locale: 'lt',
      }
    }
  }
};