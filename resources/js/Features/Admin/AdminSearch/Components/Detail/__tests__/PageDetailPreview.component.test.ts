import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import PageDetailPreview from '../PageDetailPreview.vue';

import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

const basePage = {
  id: 'p1',
  title: 'Puslapis',
  permalink: 'apie-mus',
  lang: 'lt',
  tenant_id: 5,
};

const mountPreview = (page: Record<string, unknown> = {}) =>
  mount(PageDetailPreview, {
    props: { page: { ...basePage, ...page } },
    global: { stubs: { ...commonStubs } },
  });

const publicLink = (wrapper: ReturnType<typeof mount>) =>
  wrapper.find('a[href*="/mocked-route/page?"]');

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(
    createMockPage({ tenants: [{ id: 5, alias: 'mif', shortname: 'MIF' }] }),
  );
});

describe('PageDetailPreview', () => {
  it('builds the public page URL via route() with the tenant subdomain', () => {
    const href = publicLink(mountPreview()).attributes('href');

    expect(href).toBeDefined();
    expect(href).toContain('subdomain=mif');
    expect(href).toContain('lang=lt');
    expect(href).toContain('permalink=apie-mus');
  });

  it('maps the main tenant alias (vusa) to www', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({ tenants: [{ id: 5, alias: 'vusa', shortname: 'VU SA' }] }),
    );

    expect(publicLink(mountPreview()).attributes('href')).toContain('subdomain=www');
  });

  it('falls back to www when the tenant is unknown', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ tenants: [] }));

    expect(publicLink(mountPreview()).attributes('href')).toContain('subdomain=www');
  });

  it('hides the public link when there is no permalink', () => {
    expect(publicLink(mountPreview({ permalink: '' })).exists()).toBe(false);
  });
});
