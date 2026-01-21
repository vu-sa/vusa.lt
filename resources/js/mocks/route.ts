/**
 * Unified route mock for Storybook, VitePress, and Vitest
 * 
 * Provides a simple route() function that returns predictable mock URLs.
 * No need for a manual route map - components don't actually navigate in stories/docs.
 */

export interface RouteParams {
  [key: string]: string | number | boolean | undefined;
}

/**
 * Mock route function that returns predictable URLs.
 * 
 * @param name - The Laravel route name (e.g., 'documents.index')
 * @param params - Optional route parameters
 * @param absolute - Whether to return an absolute URL (default: true)
 * @returns A mock URL like 'http://localhost:8000/mock/documents.index?id=1'
 */
export function route(name: string, params: RouteParams = {}, absolute = true): string {
  const baseUrl = absolute ? 'http://localhost:8000' : '';
  
  // Build query string from params for visibility in stories
  const paramEntries = Object.entries(params).filter(([, v]) => v !== undefined);
  const queryString = paramEntries.length > 0
    ? '?' + paramEntries.map(([k, v]) => `${k}=${encodeURIComponent(String(v))}`).join('&')
    : '';
  
  return `${baseUrl}/mock/${name}${queryString}`;
}

/**
 * Check if a route matches the current URL (always false in mocks)
 */
export function current(name?: string, params?: RouteParams): boolean {
  return false;
}

/**
 * Mock Ziggy configuration object
 * Provides minimal structure for components that inspect Ziggy directly
 */
export const Ziggy = {
  url: 'http://localhost:8000',
  port: 8000,
  defaults: {},
  routes: {} as Record<string, { uri: string; methods: string[] }>,
};

// ============================================================================
// Storybook fn()-wrapped version for spy/assertion capabilities
// ============================================================================

let routeFn: typeof route = route;

// The fn()-wrapped version will be set up in Storybook's preview.ts
// This export allows stories to import and use it directly if needed
export { routeFn };

// Default export for convenience
export default {
  route,
  current,
  Ziggy,
  routeFn,
};
