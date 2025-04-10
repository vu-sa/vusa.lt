import { vi } from 'vitest';

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

// Mock i18n functions
vi.mock('laravel-vue-i18n', () => ({
  trans: vi.fn((key: string) => key),
  transChoice: vi.fn((key: string, count: number) => key),
  createI18nInstance: vi.fn(),
}));