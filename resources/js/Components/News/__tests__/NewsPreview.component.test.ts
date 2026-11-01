import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import NewsPreview from '../NewsPreview.vue';

import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

const baseNews = {
  id: 'n1',
  title: 'Naujiena',
  permalink: 'mano-naujiena',
  lang: 'lt',
  tenant_id: 5,
};

const DRAFT = { draft: true };

const mountPreview = (news: Record<string, unknown> = {}) =>
  mount(NewsPreview, {
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

describe('NewsPreview', () => {
  it('places the status control and publication date in the facts below the header', () => {
    const wrapper = mount(NewsPreview, {
      props: { news: { ...baseNews, image: '/news.jpg' }, date: '2026-11-01' },
      global: { stubs: commonStubs },
    });
    const status = wrapper.get('[data-slot="news-preview-status"]');
    expect(status.text()).toContain('Paskelbta');
    expect(status.element.closest('[data-slot=preview-fact]')).not.toBeNull();
    expect(wrapper.get('header').find('[data-slot=news-preview-status]').exists()).toBe(false);
    expect(wrapper.get('header').text()).not.toContain('2026-11-01');
    expect(wrapper.text().match(/2026-11-01/g)).toHaveLength(1);
  });

  it('labels a future publication date as scheduled and preserves a trash date label', async () => {
    const wrapper = mount(NewsPreview, {
      props: { news: { ...baseNews, draft: false, publish_time: Math.floor(Date.now() / 1000) + 86400 }, date: 'Tomorrow' },
      global: { stubs: commonStubs },
    });
    expect(wrapper.text()).toContain('Suplanuota');
    expect(wrapper.text()).toContain('Tomorrow');
    await wrapper.setProps({ trashed: true, dateLabel: 'Ištrinta', date: 'Yesterday' });
    expect(wrapper.text()).toContain('Ištrinta');
    expect(wrapper.text()).toContain('Yesterday');
  });

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

  it('offers no public link for an unpublished draft', () => {
    expect(publicLink(mountPreview(DRAFT)).exists()).toBe(false);
  });

  it('hides the public link when there is no permalink', () => {
    expect(publicLink(mountPreview({ permalink: '' })).exists()).toBe(false);
  });
});
