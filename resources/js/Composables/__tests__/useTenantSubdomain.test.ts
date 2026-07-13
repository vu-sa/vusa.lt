import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { resolveTenantSubdomain } from '../useTenantSubdomain';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('resolveTenantSubdomain', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('maps the main tenant alias vusa to www', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        tenants: [
          { id: 1, alias: 'vusa', name: 'VU SA', shortname: 'VU SA' },
          { id: 2, alias: 'mif', name: 'VU MIF', shortname: 'VU MIF' },
        ],
      }),
    );

    expect(resolveTenantSubdomain(1)).toBe('www');
  });

  it('returns the tenant alias for non-main tenants', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        tenants: [
          { id: 1, alias: 'vusa', name: 'VU SA', shortname: 'VU SA' },
          { id: 2, alias: 'mif', name: 'VU MIF', shortname: 'VU MIF' },
        ],
      }),
    );

    expect(resolveTenantSubdomain(2)).toBe('mif');
  });

  it('falls back to www when the tenant id is unknown', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        tenants: [
          { id: 1, alias: 'vusa', name: 'VU SA', shortname: 'VU SA' },
        ],
      }),
    );

    expect(resolveTenantSubdomain(999)).toBe('www');
  });

  it('falls back to www when no tenant id is provided', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        tenants: [
          { id: 1, alias: 'vusa', name: 'VU SA', shortname: 'VU SA' },
        ],
      }),
    );

    expect(resolveTenantSubdomain()).toBe('www');
  });

  it('falls back to www when the matched tenant has no alias', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        tenants: [
          { id: 1, alias: null, name: 'VU SA', shortname: 'VU SA' },
        ],
      }),
    );

    expect(resolveTenantSubdomain(1)).toBe('www');
  });
});
