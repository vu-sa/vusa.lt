import type { Page } from '@inertiajs/core';

/**
 * Default mock page props matching the global inertia.mock.ts shape.
 */
const defaultPageProps = {
  app: {
    locale: 'lt',
    subdomain: 'www',
    name: 'VU SA',
    url: 'http://www.vusa.test',
    path: '',
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
  typesenseConfig: {
    apiKey: 'storybook-api-key',
    nodes: [{ protocol: 'http', host: 'localhost', port: 8108 }],
  },
};

/**
 * Create a mock Inertia page object for use in tests.
 *
 * Deep-merges the provided overrides with the default mock page props so
 * individual tests only need to specify what they want to change.
 *
 * @example
 * import { createMockPage } from '@/tests/helpers/createMockPage';
 * import { usePage } from '@inertiajs/vue3';
 *
 * vi.mocked(usePage).mockReturnValue(
 *   createMockPage({ app: { path: '/mano/forms/create' } })
 * );
 */
export function createMockPage(overrides: Record<string, any> = {}): any {
  return {
    props: deepMerge(defaultPageProps, overrides),
  };
}

/**
 * Simple deep merge of plain objects. Does not handle arrays specially
 * (arrays in `source` overwrite arrays in `target`).
 */
function deepMerge(target: Record<string, any>, source: Record<string, any>): Record<string, any> {
  const result = { ...target };

  for (const key of Object.keys(source)) {
    if (
      source[key] !== null
      && typeof source[key] === 'object'
      && !Array.isArray(source[key])
      && key in result
      && result[key] !== null
      && typeof result[key] === 'object'
      && !Array.isArray(result[key])
    ) {
      result[key] = deepMerge(result[key], source[key]);
    }
    else {
      result[key] = source[key];
    }
  }

  return result;
}
