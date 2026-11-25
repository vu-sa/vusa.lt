import type { Router } from '@inertiajs/core';
import { defineComponent, h, reactive, ref } from 'vue';

type SpyFactory = <Args extends unknown[] = unknown[], Result = unknown>(
  implementation?: (...args: Args) => Result,
) => (...args: Args) => Result;

// Mock Head component for document meta
const Head = defineComponent({
  name: 'InertiaHead',
  setup(_, { slots }) {
    return () => h('div', { 'data-testid': 'inertia-head' }, slots.default?.());
  },
});

// Mock Link component
const Link = defineComponent({
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

const Deferred = defineComponent({
  name: 'InertiaDeferred',
  setup(_, { slots }) {
    return () => slots.default?.();
  },
});

const WhenVisible = defineComponent({
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

export function createInertiaMock(spy: SpyFactory) {
  const useRemember = spy((data: object, _key?: string) => ref(data));

  // Registry for router event listeners (used in tests)
  const listeners: Record<string, Array<(event: unknown) => void>> = {};

  // Mock router for Inertia
  const router = {
    visit: spy((url: string, options?: unknown) => {
      console.log('Inertia router visit:', url, options);
      return Promise.resolve();
    }),
    get: spy((url: string, data?: unknown, options?: unknown) => {
      console.log('Inertia router get:', url, data, options);
      return Promise.resolve();
    }),
    post: spy((url: string, data?: unknown, options?: unknown) => {
      console.log('Inertia router post:', url, data, options);
      return Promise.resolve();
    }),
    put: spy((url: string, data?: unknown, options?: unknown) => {
      console.log('Inertia router put:', url, data, options);
      return Promise.resolve();
    }),
    patch: spy((url: string, data?: unknown, options?: unknown) => {
      console.log('Inertia router patch:', url, data, options);
      return Promise.resolve();
    }),
    delete: spy((url: string, options?: unknown) => {
      console.log('Inertia router delete:', url, options);
      return Promise.resolve();
    }),
    restore: spy(),
    remember: spy(),
    push: spy(),
    replace: spy(),
    reload: spy((options?: Parameters<Router['reload']>[0]) => {
      console.log('Inertia router reload:', options);
      return Promise.resolve();
    }),
    on: spy((event: string, callback: (event: unknown) => void) => {
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
    __trigger: (eventName: string, event?: unknown) => {
      [...(listeners[eventName] ?? [])].forEach(cb => cb(event));
    },
    __triggerBefore: (event: unknown) => {
      [...(listeners.before ?? [])].forEach(cb => cb(event));
    },
  };

  // Mock useForm for Inertia forms
  const formVisit = () => spy<[url: string, options?: Record<string, unknown>], void>();

  const useForm = spy((keyOrData: string | Record<string, unknown> = {}, rememberedData?: Record<string, unknown>) => {
    // `useForm('RememberKey', data)` keeps a draft across navigations; the mock only needs the data.
    const data = typeof keyOrData === 'string' ? (rememberedData ?? {}) : keyOrData;
    const fields = reactive({
      ...data,
      data: () => data,
      errors: {},
      hasErrors: false,
      isDirty: false,
      processing: false,
      progress: null,
      wasSuccessful: false,
      recentlySuccessful: false,
      defaults: spy(),
      reset: spy(),
      clearErrors: spy(),
      setError: spy(),
      submit: spy(),
      get: formVisit(),
      post: formVisit(),
      put: formVisit(),
      patch: formVisit(),
      delete: formVisit(),
      cancel: spy(),
      validating: false,
      valid: spy((_field?: string) => false),
      invalid: spy((_field?: string) => false),
    });

    type ChainMethod = (...args: unknown[]) => MockForm;
    type MockForm = typeof fields & {
      transform: ChainMethod;
      withPrecognition: ChainMethod;
      validate: ChainMethod;
      setValidationTimeout: ChainMethod;
    };
    const chain: ChainMethod = () => form;
    const form: MockForm = Object.assign(fields, {
      transform: spy(chain),
      withPrecognition: spy(chain),
      validate: spy(chain),
      setValidationTimeout: spy(chain),
    });

    return form;
  });

  // Mock useHttp, Inertia v3's standalone XHR hook. Reactive so components that read
  // `http.processing` in a computed re-render; callers drive the callbacks themselves via
  // the spy, e.g. vi.mocked(http.get).mock.calls[0][1].onSuccess(payload).
  const useHttp = spy((data: Record<string, unknown> = {}) => reactive({
    ...data,
    processing: false,
    errors: {},
    hasErrors: false,
    response: null,
    get: spy(),
    post: spy(),
    put: spy(),
    patch: spy(),
    delete: spy(),
    cancel: spy(),
    reset: spy(),
    clearErrors: spy(),
  }));

  return { useRemember, router, useForm, useHttp, Head, Link, Deferred, WhenVisible };
}
