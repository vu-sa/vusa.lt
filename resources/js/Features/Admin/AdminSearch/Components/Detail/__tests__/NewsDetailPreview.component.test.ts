import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';
import NewsDetailPreview from '../NewsDetailPreview.vue';

const baseNews = {
  id: 'n1',
  title: 'Naujiena',
  permalink: 'mano-naujiena',
  lang: 'lt',
  tenant_id: 5,
};

const mountPreview = (news: Record<string, unknown> = {}) =>
  mount(NewsDetailPreview, {
    props: { news: { ...baseNews, ...news } },
    global: { stubs: { ...commonStubs } },
  });

const publicLink = (wrapper: ReturnType<typeof mount>) =>
  wrapper.find('a[href*="/mocked-route/news?"]');

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(
    createMockPage({ tenants: [{ id: 5, alias: 'mif', shortname: 'MIF' }] }),
  );
});

describe('NewsDetailPreview', () => {
  it('builds the public news URL via route() with the tenant subdomain', () => {
    const href = publicLink(mountPreview()).attributes('href');

    expect(href).toBeDefined();
    expect(href).toContain('subdomain=mif');
    expect(href).toContain('newsString=naujiena');
    expect(href).toContain('news=mano-naujiena');
  });

  it('uses the English news slug for en pages', () => {
    const href = publicLink(mountPreview({ lang: 'en' })).attributes('href');

    expect(href).toContain('lang=en');
    expect(href).toContain('newsString=news');
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
