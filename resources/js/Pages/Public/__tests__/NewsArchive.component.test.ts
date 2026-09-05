import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import NewsArchive from '@/Pages/Public/NewsArchive.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import type { NewsItem } from '@/Types/contentParts';

const sampleNews: NewsItem[] = [
  {
    id: 1,
    title: 'Pirmasis svarbus pranešimas',
    short: 'Trumpas pirmojo pranešimo tekstas apie studentų atstovavimą.',
    permalink: 'pirmasis-pranesimas',
    image: '/images/news1.jpg',
    publish_time: '2026-09-01T10:00:00.000Z',
    lang: 'lt',
    category: 'Atstovavimas',
  },
  {
    id: 2,
    title: 'Antrasis įvykis bendruomenėje',
    short: 'Trumpas antrojo įvykio tekstas.',
    permalink: 'antrasis-ivykis',
    image: '/images/news2.jpg',
    publish_time: '2026-08-25T10:00:00.000Z',
    lang: 'lt',
    category: 'Renginiai',
  },
];

describe('Public/NewsArchive.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.history.replaceState({}, '', '/lt/naujienos');

    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        app: { locale: 'lt' },
        tenant: {
          shortname: 'VU SA',
          fullname: 'Vilniaus universiteto Studentų atstovybė',
          subdomain: 'www',
        },
        typesenseConfig: {
          apiKey: 'test-key',
          nodes: [{ protocol: 'http', host: 'localhost', port: 8108 }],
          collections: {
            public_news: 'public_news',
          },
        },
      }),
    );
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  function mountPage(props: Record<string, unknown> = {}) {
    return mount(NewsArchive, {
      props: {
        news: {
          data: sampleNews,
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 2,
          path: '/lt/naujienos',
          links: [],
        },
        allCategories: [
          { id: 1, name: 'Atstovavimas' },
          { id: 2, name: 'Renginiai' },
        ],
        allTenants: [
          { id: 16, shortname: 'VU SA' },
          { id: 11, shortname: 'VU SA MIF' },
        ],
        ...props,
      },
      global: {
        stubs: {
          PublicBreadcrumbs: true,
        },
      },
    });
  }

  it('renders page title band with title and eyebrow', () => {
    const wrapper = mountPage();
    expect(wrapper.text()).toContain('Naujienos');
    expect(wrapper.text()).toContain('VU SA');
    expect(wrapper.text()).toContain('Kas vyksta Studentų atstovybėje ir universitete');
  });

  it('renders total count indicator and does not show category tabs', () => {
    const wrapper = mountPage();
    expect(wrapper.text()).toContain('Rasta :count naujienų');
    // Category quick tabs should not be rendered
    expect(wrapper.text()).not.toContain('Visos');
  });

  it('renders search bar and filter controls', () => {
    const wrapper = mountPage();
    const searchInput = wrapper.find('input[type="text"]');
    expect(searchInput.exists()).toBe(true);
    expect(wrapper.text()).toContain('Filtrai');
    expect(wrapper.text()).toContain('Rikiuoti');
  });

  it('always renders first article as featured and remaining articles in grid without duplicate heading', () => {
    const wrapper = mountPage();
    // Featured article title
    expect(wrapper.text()).toContain('Pirmasis svarbus pranešimas');
    // Second article in grid
    expect(wrapper.text()).toContain('Antrasis įvykis bendruomenėje');
    // "Visos naujienos" heading should not be present
    expect(wrapper.text()).not.toContain('Visos naujienos');
  });

  it('renders empty state when news is empty', () => {
    const wrapper = mountPage({
      news: {
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        path: '/lt/naujienos',
        links: [],
      },
    });

    expect(wrapper.text()).toContain('Naujienų nerasta');
  });

  it('renders first article as featured even when search query is typed', async () => {
    const wrapper = mountPage();
    const searchInput = wrapper.find('input[type="text"]');
    await searchInput.setValue('pranešimas');

    expect(wrapper.text()).toContain('Pirmasis svarbus pranešimas');
    expect(wrapper.text()).not.toContain('Visos naujienos');
  });

  it('has filter bar closed by default and toggles on button click', async () => {
    localStorage.clear();
    const wrapper = mountPage();
    const filterBtn = wrapper.findAll('button').find(b => b.text().includes('Filtrai'));
    expect(filterBtn?.exists()).toBe(true);

    const filterPopoversBar = wrapper.find('.border-t.border-border\\/60');
    expect(filterPopoversBar.isVisible()).toBe(false);

    await filterBtn?.trigger('click');
    expect(filterPopoversBar.isVisible()).toBe(true);
  });
});
