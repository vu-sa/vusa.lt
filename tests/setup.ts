import { vi } from 'vitest';
import { config } from '@vue/test-utils';

// Note: Icon auto-imports now handled by unplugin-icons in vitest.config.ts
// No need to manually mock individual icons anymore

// Node 26 defines `localStorage`/`sessionStorage` as native globals whose getter returns
// undefined unless the process was started with --localstorage-file. Because that property
// already exists on globalThis (which *is* the jsdom window), jsdom never installs its own —
// leaving both undefined. Provide a fresh in-memory Storage per test file instead.
const createStorage = (): Storage => {
  const entries = new Map<string, string>();

  return {
    get length() {
      return entries.size;
    },
    key: (index: number) => [...entries.keys()][index] ?? null,
    getItem: (key: string) => entries.get(String(key)) ?? null,
    setItem: (key: string, value: string) => void entries.set(String(key), String(value)),
    removeItem: (key: string) => void entries.delete(String(key)),
    clear: () => entries.clear(),
  } as Storage;
};

for (const key of ['localStorage', 'sessionStorage'] as const) {
  Object.defineProperty(globalThis, key, {
    value: createStorage(),
    configurable: true,
    writable: true,
  });
}

// Mock route function from Ziggy
vi.mock('ziggy-js', () => ({
  default: (name: string, params: any) => {
    return `/mocked-route/${name}`;
  },
  route: (name: string, params: any) => {
    return `/mocked-route/${name}`;
  },
}));

// Stub global route() for composable unit tests (not covered by config.global.mocks)
vi.stubGlobal('route', (name: string, params: any) => {
  const paramEntries = params ? Object.entries(params).filter(([, v]) => v !== undefined) : [];
  const queryString = paramEntries.length > 0
    ? `?${paramEntries.map(([k, v]) => `${k}=${encodeURIComponent(String(v))}`).join('&')}`
    : '';
  return `/mocked-route/${name}${queryString}`;
});

// Mock InertiaJS using centralized mock
vi.mock('@inertiajs/vue3', async () => {
  const inertiaMock = await import('../resources/js/mocks/inertia.mock.ts');
  return {
    Link: inertiaMock.Link,
    Head: inertiaMock.Head,
    usePage: inertiaMock.usePage,
    router: inertiaMock.router,
    useForm: inertiaMock.useForm,
    useHttp: inertiaMock.useHttp,
  };
});

// Helper function for translations - used by both imports and template globals
const translateFn = (key: string, params?: any) => key;

// Mock i18n functions
vi.mock('laravel-vue-i18n', () => ({
  trans: translateFn,
  transChoice: vi.fn((key: string, count: number, params?: any) => key),
  createI18nInstance: vi.fn(),
  loadLanguageAsync: vi.fn(),
  // Components that pick between lt/en label maps read this at render time.
  getActiveLanguage: vi.fn(() => 'lt'),
}));

// Add global properties to Vue component instances for testing

config.global.mocks = {
  // Mock $t template global property
  $t: translateFn,
  // Mock $tChoice template global property
  $tChoice: (key: string, count: number, params?: any) => key,
  // Add route helper as a global property if used in templates
  route: (name: string, params: any) => `/mocked-route/${name}`,
  // Add $page using centralized mock data
  $page: {
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
    },
  },
};

// Enhanced TypesenseInstantSearchAdapter mock for comprehensive network mocking
vi.mock('typesense-instantsearch-adapter', () => {
  return {
    default: vi.fn(() => ({
      searchClient: {
        search: vi.fn().mockResolvedValue({
          results: [{
            hits: [],
            facets: {},
            nbHits: 0,
            page: 0,
            nbPages: 1,
            hitsPerPage: 24,
            exhaustiveNbHits: true,
            query: '',
            params: '',
          }],
        }),
        searchForFacetValues: vi.fn().mockResolvedValue([]),
        clearCache: vi.fn(),
        addAlgoliaAgent: vi.fn(),
        initIndex: vi.fn(() => ({
          search: vi.fn().mockResolvedValue({ hits: [], nbHits: 0 }),
          searchForFacetValues: vi.fn().mockResolvedValue([]),
        })),
      },
    })),
  };
});

// Mock fetch for any remaining network calls
Object.defineProperty(global, 'fetch', {
  writable: true,
  value: vi.fn().mockResolvedValue({
    ok: true,
    status: 200,
    json: vi.fn().mockResolvedValue({ hits: [], found: 0, facet_counts: [] }),
    text: vi.fn().mockResolvedValue(''),
  }),
});

// Add global provide/inject mocks for commonly used dependencies
config.global.provide = {
  // Add any app-level dependencies that might be injected
  'app-config': {
    locale: 'lt',
    debug: false,
  },
};
