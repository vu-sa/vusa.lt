import { fn } from 'storybook/test';
import { defineComponent, h } from 'vue';

/**
 * Inertia.js mock specifically for Storybook
 * This file does NOT import from vitest to avoid browser compatibility issues
 * 
 * Use this in .storybook/preview.ts and story files
 * Use inertia.mock.ts for Vitest tests
 */

// Mock usePage() function returning commonly used page props
export const usePage = fn(() => ({
  props: {
    app: {
      locale: 'lt',
      subdomain: 'www',
      name: 'VU SA',
      url: 'http://www.vusa.test'
    },
    auth: {
      user: {
        id: 1,
        name: 'Test User',
        email: 'test@vusa.lt',
        current_duties: [
          { 
            institution: { 
              id: 'inst1', 
              name: 'VU SA Fakulteto taryba', 
              shortname: 'VU SA FT',
              types: [{ title: 'Taryba' }],
              last_meeting_date: '2024-01-15',
              active_check_in: false,
              meetings: []
            },
            role: 'admin'
          },
          { 
            institution: { 
              id: 'inst2', 
              name: 'VU SA Parlamentas', 
              shortname: 'VU SA P',
              types: [{ title: 'Parlamentas' }],
              last_meeting_date: '2024-01-10',
              active_check_in: false,
              meetings: []
            },
            role: 'member'
          }
        ]
      },
      can: {
        create: { meeting: true, document: true },
        read: { meeting: true, document: true },
        update: { meeting: true, document: true },
        delete: { meeting: true, document: true }
      }
    },
    // Accessible institutions for admin users (InstitutionSelectorForm uses this)
    accessibleInstitutions: [
      { 
        id: 'inst1', 
        name: 'VU SA Fakulteto taryba', 
        shortname: 'VU SA FT',
        tenant: { id: 'vusa', shortname: 'VU SA' },
        last_meeting_date: '2024-01-15',
        active_check_in: false,
        meetings: [{ id: 1 }, { id: 2 }]
      },
      { 
        id: 'inst2', 
        name: 'VU SA Parlamentas', 
        shortname: 'VU SA P',
        tenant: { id: 'vusa', shortname: 'VU SA' },
        last_meeting_date: '2024-01-10',
        active_check_in: false,
        meetings: [{ id: 3 }]
      },
      { 
        id: 'inst3', 
        name: 'VU Senatas', 
        shortname: 'Senatas',
        tenant: { id: 'vusa', shortname: 'VU SA' },
        last_meeting_date: null,
        active_check_in: false,
        meetings: []
      },
    ],
    tenants: [
      { id: 'vusa', name: 'VU SA', shortname: 'VU SA' },
      { id: 'vuif', name: 'VU IF', shortname: 'VU IF' },
      { id: 'vumif', name: 'VU MIF', shortname: 'VU MIF' }
    ],
    flash: {
      success: null,
      error: null,
      info: null,
      warning: null
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
          port: 8108
        }
      ]
    }
  }
}));

// Mock router for Inertia
export const router = {
  visit: fn((url: string, options?: unknown) => {
    console.log('Inertia router visit:', url, options)
    return Promise.resolve()
  }),
  get: fn((url: string, data?: unknown, options?: unknown) => {
    console.log('Inertia router get:', url, data, options)
    return Promise.resolve()
  }),
  post: fn((url: string, data?: unknown, options?: unknown) => {
    console.log('Inertia router post:', url, data, options)
    return Promise.resolve()
  }),
  put: fn((url: string, data?: unknown, options?: unknown) => {
    console.log('Inertia router put:', url, data, options)
    return Promise.resolve()
  }),
  patch: fn((url: string, data?: unknown, options?: unknown) => {
    console.log('Inertia router patch:', url, data, options)
    return Promise.resolve()
  }),
  delete: fn((url: string, options?: unknown) => {
    console.log('Inertia router delete:', url, options)
    return Promise.resolve()
  }),
  reload: fn((options?: unknown) => {
    console.log('Inertia router reload:', options)
    return Promise.resolve()
  })
};

// Mock useForm for Inertia forms
export const useForm = fn((data: Record<string, unknown> = {}) => ({
  data,
  errors: {},
  hasErrors: false,
  processing: false,
  progress: null,
  wasSuccessful: false,
  recentlySuccessful: false,
  transform: fn(),
  defaults: fn(),
  reset: fn(),
  clearErrors: fn(),
  setError: fn(),
  submit: fn(),
  get: fn(),
  post: fn(),
  put: fn(),
  patch: fn(),
  delete: fn(),
  cancel: fn()
}));

// Mock Head component for document meta
export const Head = defineComponent({
  name: 'InertiaHead',
  setup(_, { slots }) {
    return () => h('div', { 'data-testid': 'inertia-head' }, slots.default?.())
  }
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
    queryStringArrayFormat: String
  },
  setup(props, { slots }) {
    return () => h('a', {
      href: props.href,
      'data-testid': 'inertia-link',
      onClick: (e: Event) => {
        e.preventDefault()
        console.log('Inertia Link clicked:', props.href)
      }
    }, slots.default?.())
  }
});

// Export default
export default {
  usePage,
  router,
  useForm,
  Head,
  Link
};
