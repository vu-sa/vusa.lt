import { describe, it, expect, vi, beforeEach } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { useTenantOptions } from '@/Composables/useTenantOptions';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const tenants = [
  { id: 1, alias: 'vusa', fullname: 'VU studentų atstovybė', type: 'pagrindinis', primary_institution: null },
  { id: 2, alias: 'mif', fullname: 'VU MIF studentų atstovybė VU MIF', type: 'padalinys', primary_institution: { short_name: 'VU MIF', image_url: '/mif.png' } },
  { id: 3, alias: 'ff', fullname: 'VU FF studentų atstovybė VU FF', type: 'padalinys', primary_institution: null },
  // Included: a padalinys past the old hardcoded `id <= 17` cutoff, which used to hide it.
  { id: 18, alias: 'newest', fullname: 'Newest studentų atstovybė Newest', type: 'padalinys', primary_institution: null },
  // Excluded: PKP tenants are student initiatives with no subdomain to switch to.
  { id: 4, alias: 'other', fullname: 'Other studentų atstovybė Other', type: 'pkp', primary_institution: null },
];

describe('useTenantOptions', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      tenants,
      tenant: { alias: 'mif', shortname: 'VU MIF' },
      app: { path: 'lt' },
    }));
  });

  it('includes every representational tenant and excludes pkp', () => {
    const { options } = useTenantOptions();

    expect(options.value.map(option => option.key)).toEqual(['vusa', 'mif', 'ff', 'newest']);
  });

  it('derives the label from the part of fullname after "atstovybė "', () => {
    const { options } = useTenantOptions();

    expect(options.value.find(option => option.key === 'mif')?.label).toBe('VU MIF');
  });

  it('marks the pagrindinis tenant as the main office', () => {
    const { options } = useTenantOptions();

    expect(options.value.find(option => option.key === 'vusa')?.isMainOffice).toBe(true);
    expect(options.value.find(option => option.key === 'mif')?.isMainOffice).toBe(false);
  });

  it('prepends any extra options passed in', () => {
    const { options } = useTenantOptions([{ key: 'extra', label: 'Extra' }]);

    expect(options.value[0]).toEqual({ key: 'extra', label: 'Extra' });
  });

  it('isActive matches the current tenant alias', () => {
    const { isActive } = useTenantOptions();

    expect(isActive('mif')).toBe(true);
    expect(isActive('ff')).toBe(false);
  });

  it('currentLabel falls back to the main tenant label when on the main tenant', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ tenants, tenant: { alias: 'vusa', shortname: 'VU SA' } }));

    const { currentLabel } = useTenantOptions();

    expect(currentLabel('Visi padaliniai').value).toBe('Visi padaliniai');
    expect(currentLabel().value).toBe('Padaliniai');
  });

  it('currentLabel uses the last word of the tenant shortname otherwise', () => {
    const { currentLabel } = useTenantOptions();

    expect(currentLabel().value).toBe('MIF');
  });

  it('switchTenant keeps the current path when the page supports tenant-scoped content', () => {
    const mockPage = createMockPage({ tenants, tenantSwitchTarget: 'same-page' });
    mockPage.url = '/lt/kontaktai';
    vi.mocked(usePage).mockReturnValue(mockPage);
    Object.defineProperty(window, 'location', {
      value: { host: 'mif.vusa.test', protocol: 'https:', href: '' },
      writable: true,
      configurable: true,
    });

    const { switchTenant } = useTenantOptions();
    switchTenant('ff');

    expect(window.location.href).toBe('https://ff.vusa.test/lt/kontaktai');
  });

  it('switchTenant goes to the selected tenant home page when the current page is not tenant-scoped', () => {
    const mockPage = createMockPage({ tenants, app: { locale: 'en' } });
    mockPage.url = '/en/documents';
    vi.mocked(usePage).mockReturnValue(mockPage);
    Object.defineProperty(window, 'location', {
      value: { host: 'www.vusa.test', protocol: 'https:', href: '' },
      writable: true,
      configurable: true,
    });

    const { switchTenant } = useTenantOptions();
    switchTenant('ff');

    expect(window.location.href).toBe('https://ff.vusa.test/en');
  });

  it('switchTenant maps the "vusa" alias to the "www" subdomain', () => {
    const mockPage = createMockPage({ tenants, tenantSwitchTarget: 'same-page' });
    mockPage.url = '/lt';
    vi.mocked(usePage).mockReturnValue(mockPage);
    Object.defineProperty(window, 'location', {
      value: { host: 'mif.vusa.test', protocol: 'https:', href: '' },
      writable: true,
      configurable: true,
    });

    const { switchTenant } = useTenantOptions();
    switchTenant('vusa');

    expect(window.location.href).toBe('https://www.vusa.test/lt');
  });
});
