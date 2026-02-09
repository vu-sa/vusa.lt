/**
 * Creates a mock Tenant entity
 * Note: Uses type assertion for test mocks where not all properties are needed
 */
export function createMockTenant(
  overrides: Record<string, unknown> = {},
): App.Entities.Tenant {
  return {
    id: 1,
    fullname: 'Vilniaus universiteto Studentų atstovybė',
    shortname: 'VU SA',
    alias: 'vusa',
    type: 'pagrindinis',
    primary_institution_id: '1',
    // Counts
    banners_count: 2,
    calendar_count: 1,
    duties_count: 50,
    institutions_count: 10,
    news_count: 0,
    pages_count: 0,
    quick_links_count: 0,
    resources_count: 0,
    // Exists
    banners_exists: true,
    calendar_exists: true,
    duties_exists: true,
    institutions_exists: true,
    news_exists: false,
    pages_exists: false,
    quick_links_exists: false,
    resources_exists: false,
    primary_institution_exists: true,
    content_exists: false,
    ...overrides,
  } as App.Entities.Tenant;
}

/**
 * Common tenants for testing - matches the mocks in inertia.mock.ts
 */
export const mockTenants = {
  vusa: createMockTenant({
    id: 1,
    fullname: 'Vilniaus universiteto Studentų atstovybė',
    shortname: 'VU SA',
    alias: 'vusa',
  }),
  vuif: createMockTenant({
    id: 2,
    fullname: 'VU SA Istorijos fakultete',
    shortname: 'VU SA IF',
    alias: 'if',
    type: 'padalinys',
  }),
  vumif: createMockTenant({
    id: 3,
    fullname: 'VU SA Matematikos ir informatikos fakultete',
    shortname: 'VU SA MIF',
    alias: 'mif',
    type: 'padalinys',
  }),
};

/**
 * Returns array of tenants for testing
 */
export function getMockTenantsList(): App.Entities.Tenant[] {
  return Object.values(mockTenants);
}
