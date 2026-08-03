import { describe, it, expect, vi, beforeEach } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { useTenantOptions } from '@/Composables/useTenantOptions';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const tenants = [
  { id: 1, alias: 'vusa', fullname: 'VU studentų atstovybė', type: 'pagrindinis', primary_institution: null },
  { id: 2, alias: 'mif', fullname: 'VU MIF studentų atstovybė VU MIF', type: 'padalinys', primary_institution: { short_name: 'VU MIF', image_url: '/mif.png' } },
  { id: 3, alias: 'ff', fullname: 'VU FF studentų atstovybė VU FF', type: 'padalinys', primary_institution: null },
  // Excluded: id above the cutoff used by the switcher.
  { id: 18, alias: 'excluded', fullname: 'Excluded studentų atstovybė Excluded', type: 'padalinys', primary_institution: null },
  // Excluded: not a padalinys/pagrindinis tenant.
  { id: 4, alias: 'other', fullname: 'Other studentų atstovybė Other', type: 'other', primary_institution: null },
];

describe('useTenantOptions', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      tenants,
      tenant: { alias: 'mif', shortname: 'VU MIF' },
      app: { path: 'lt' },
    }));
  });

  it('filters tenants to padalinys/pagrindinis with id <= 17', () => {
    const { options } = useTenantOptions();

    expect(options.value.map(option => option.key)).toEqual(['vusa', 'mif', 'ff']);
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

  it.each([
    ['lt', true],
    ['en', true],
    ['lt/naujienos', true],
    ['lt/kontaktai', true],
    ['en/contacts', true],
    ['lt/naujienos/some-slug', false],
  ])('isSwitchAllowed is %s for path "%s"', (path, expected) => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ tenants, app: { path } }));

    const { isSwitchAllowed } = useTenantOptions();

    expect(isSwitchAllowed.value).toBe(expected);
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

  it('switchTenant navigates to the target subdomain, keeping the current path', () => {
    const mockPage = createMockPage({ tenants });
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

  it('switchTenant maps the "vusa" alias to the "www" subdomain', () => {
    const mockPage = createMockPage({ tenants });
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
