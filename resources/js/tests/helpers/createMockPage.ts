import { defaultPageProps } from '@/mocks/defaultPageProps';

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
 *   createMockPage({ app: { path: 'mano/forms/create' } })
 * );
 */
export function createMockPage(overrides: Record<string, any> = {}): any {
  return {
    props: deepMerge(defaultPageProps(), overrides),
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
