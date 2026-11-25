import { vi } from 'vitest';
import { defineComponent, h, reactive, ref } from 'vue';

import { defaultPageProps } from './defaultPageProps';

export const useRemember = vi.fn((data: object) => ref(data));

export const usePage = vi.fn(() => ({ props: defaultPageProps() }));

// Registry for router event listeners (used in tests)
const listeners: Record<string, Array<(event: any) => void>> = {};

// Mock router for Inertia
export const router = {
  visit: vi.fn((url: string, options?: any) => {
    console.log('Inertia router visit:', url, options);
    return Promise.resolve();
  }),
  get: vi.fn((url: string, data?: any, options?: any) => {
    console.log('Inertia router get:', url, data, options);
    return Promise.resolve();
  }),
  post: vi.fn((url: string, data?: any, options?: any) => {
    console.log('Inertia router post:', url, data, options);
    return Promise.resolve();
  }),
  put: vi.fn((url: string, data?: any, options?: any) => {
    console.log('Inertia router put:', url, data, options);
    return Promise.resolve();
  }),
  patch: vi.fn((url: string, data?: any, options?: any) => {
    console.log('Inertia router patch:', url, data, options);
    return Promise.resolve();
  }),
  delete: vi.fn((url: string, options?: any) => {
    console.log('Inertia router delete:', url, options);
    return Promise.resolve();
  }),
  restore: vi.fn(),
  remember: vi.fn(),
  push: vi.fn(),
  replace: vi.fn(),
  reload: vi.fn((options?: any) => {
    console.log('Inertia router reload:', options);
    return Promise.resolve();
  }),
  on: vi.fn((event: string, callback: any) => {
    (listeners[event] ??= []).push(callback);
    return () => {
      const callbacks = listeners[event] ?? [];
      const index = callbacks.indexOf(callback);
      if (index > -1) {
        callbacks.splice(index, 1);
      }
    };
  }),
  // Test helpers to fire router events at registered listeners
  __trigger: (eventName: string, event?: any) => {
    [...(listeners[eventName] ?? [])].forEach(cb => cb(event));
  },
  __triggerBefore: (event: any) => {
    [...(listeners.before ?? [])].forEach(cb => cb(event));
  },
};

// Mock useForm for Inertia forms
export const useForm = vi.fn((keyOrData: any = {}, rememberedData?: any) => {
  // `useForm('RememberKey', data)` keeps a draft across navigations; the mock only needs the data.
  const data = typeof keyOrData === 'string' ? (rememberedData ?? {}) : keyOrData;
  const form: Record<string, any> = reactive({
    ...data,
    data: () => data,
    errors: {},
    hasErrors: false,
    isDirty: false,
    processing: false,
    progress: null,
    wasSuccessful: false,
    recentlySuccessful: false,
    defaults: vi.fn(),
    reset: vi.fn(),
    clearErrors: vi.fn(),
    setError: vi.fn(),
    submit: vi.fn(),
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    cancel: vi.fn(),
    validating: false,
    valid: vi.fn(() => false),
    invalid: vi.fn(() => false),
  });

  // Inertia's transform() is chainable: form.transform(fn).post(...).
  form.transform = vi.fn(() => form);
  form.withPrecognition = vi.fn(() => form);
  form.validate = vi.fn(() => form);
  form.setValidationTimeout = vi.fn(() => form);

  return form;
});

// Mock useHttp, Inertia v3's standalone XHR hook. Reactive so components that read
// `http.processing` in a computed re-render; callers drive the callbacks themselves via
// the spy, e.g. vi.mocked(http.get).mock.calls[0][1].onSuccess(payload).
export const useHttp = vi.fn((data: any = {}) => reactive({
  ...data,
  processing: false,
  errors: {},
  hasErrors: false,
  response: null,
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
  cancel: vi.fn(),
  reset: vi.fn(),
  clearErrors: vi.fn(),
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
    prefetch: { type: [Boolean, String, Array], default: false },
    cacheFor: [String, Number, Array],
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

export const Deferred = defineComponent({
  name: 'InertiaDeferred',
  setup(_, { slots }) {
    return () => slots.default?.();
  },
});

export const WhenVisible = defineComponent({
  name: 'WhenVisible',
  props: {
    data: [String, Array],
    buffer: { type: Number, default: 0 },
    always: { type: Boolean, default: false },
  },
  setup(_, { slots }) {
    return () => h('div', { 'data-testid': 'inertia-when-visible' }, slots.default?.({ fetching: false }));
  },
});

// Export default
export default {
  usePage,
  router,
  useForm,
  useHttp,
  Head,
  Link,
  Deferred,
  WhenVisible,
};
