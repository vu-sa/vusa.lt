import { fn } from 'storybook/test';
import { vi } from 'vitest';
import { defineComponent, h } from 'vue';

/**
 * Centralized Inertia.js mock for Storybook and Vitest
 * Used across all stories and tests that need Inertia functionality
 */

// Detect if we're in a Vitest environment
const isVitest = typeof vi !== 'undefined';

// Use a unified mock function type to avoid TypeScript conflicts
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const mockFn = (isVitest ? vi.fn : fn) as <T extends (...args: any[]) => any>(impl?: T) => T;

// Mock usePage() function returning commonly used page props
export const usePage = mockFn(() => ({
  props: {
    app: {
      locale: 'lt',
      subdomain: 'www',
      name: 'VU SA',
      url: 'http://www.vusa.test',
    },
    auth: {
      user: {
        id: 1,
        name: 'Test User',
        email: 'test@vusa.lt',
        current_duties: [
          {
            institution: {
              id: 'vusa',
              name: 'VU SA',
              shortname: 'VU SA',
            },
            role: 'admin',
          },
        ],
      },
      can: {
        create: { meeting: true, document: true },
        read: { meeting: true, document: true },
        update: { meeting: true, document: true },
        delete: { meeting: true, document: true },
      },
    },
    tenants: [
      { id: 'vusa', name: 'VU SA', shortname: 'VU SA' },
      { id: 'vuif', name: 'VU IF', shortname: 'VU IF' },
      { id: 'vumif', name: 'VU MIF', shortname: 'VU MIF' },
    ],
    flash: {
      success: null,
      error: null,
      info: null,
      warning: null,
    },
    errors: {},
    otherLangURL: '/en',
    // Add Typesense config for document search stories
    typesenseConfig: {
      apiKey: 'storybook-api-key',
      nodes: [
        {
          protocol: 'http',
          host: 'localhost',
          port: 8108,
        },
      ],
    },
  },
}));

// Mock router for Inertia
export const router = {
  visit: mockFn((url: string, options?: any) => {
    console.log('Inertia router visit:', url, options);
    return Promise.resolve();
  }),
  get: mockFn((url: string, data?: any, options?: any) => {
    console.log('Inertia router get:', url, data, options);
    return Promise.resolve();
  }),
  post: mockFn((url: string, data?: any, options?: any) => {
    console.log('Inertia router post:', url, data, options);
    return Promise.resolve();
  }),
  put: mockFn((url: string, data?: any, options?: any) => {
    console.log('Inertia router put:', url, data, options);
    return Promise.resolve();
  }),
  patch: mockFn((url: string, data?: any, options?: any) => {
    console.log('Inertia router patch:', url, data, options);
    return Promise.resolve();
  }),
  delete: mockFn((url: string, options?: any) => {
    console.log('Inertia router delete:', url, options);
    return Promise.resolve();
  }),
  reload: mockFn((options?: any) => {
    console.log('Inertia router reload:', options);
    return Promise.resolve();
  }),
};

// Mock useForm for Inertia forms
export const useForm = mockFn((data: any = {}) => ({
  data,
  errors: {},
  hasErrors: false,
  processing: false,
  progress: null,
  wasSuccessful: false,
  recentlySuccessful: false,
  transform: mockFn(),
  defaults: mockFn(),
  reset: mockFn(),
  clearErrors: mockFn(),
  setError: mockFn(),
  submit: mockFn(),
  get: mockFn(),
  post: mockFn(),
  put: mockFn(),
  patch: mockFn(),
  delete: mockFn(),
  cancel: mockFn(),
}));

// Mock Head component for document meta
export const Head = defineComponent({
  name: 'InertiaHead',
  setup(_, { slots }) {
    return () => h('div', { 'data-testid': 'inertia-head' }, slots.default?.());
  },
});

// Mock Link component
export const Link = defineComponent({
  name: 'InertiaLink',
  props: {
    href: String,
    method: { type: String, default: 'get' },
    data: Object,
    replace: Boolean,
    preserveScroll: Boolean,
    preserveState: Boolean,
    only: Array,
    headers: Object,
    queryStringArrayFormat: String,
  },
  setup(props, { slots }) {
    return () => h('a', {
      'href': props.href,
      'data-testid': 'inertia-link',
      'onClick': (e: Event) => {
        e.preventDefault();
        console.log('Inertia Link clicked:', props.href);
      },
    }, slots.default?.());
  },
});

// Export default
export default {
  usePage,
  router,
  useForm,
  Head,
  Link,
};
