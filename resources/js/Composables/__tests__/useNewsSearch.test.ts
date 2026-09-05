import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';

const mockSearch = vi.fn().mockResolvedValue({
  hits: [
    {
      document: {
        id: '1',
        title: 'VU SA Konferencija',
        short: 'Trumpas aprašymas',
        permalink: 'konferencija-2026',
        image: '/images/test.jpg',
        publish_time: 1755610000,
        lang: 'lt',
        tenant_id: 16,
        tenant_shortname: 'VU SA',
        category_name: 'Atstovavimas',
        tag_names: ['Studijos'],
        year: 2026,
      },
    },
  ],
  found: 1,
  facet_counts: [
    {
      field_name: 'category_name',
      counts: [{ value: 'Atstovavimas', count: 1 }],
    },
    {
      field_name: 'tenant_shortname',
      counts: [{ value: 'VU SA', count: 1 }],
    },
    {
      field_name: 'year',
      counts: [{ value: '2026', count: 1 }],
    },
    {
      field_name: 'tag_names',
      counts: [{ value: 'Studijos', count: 1 }],
    },
  ],
});

vi.mock('@/Shared/Search/services/SearchClientFactory', () => ({
  SearchClientFactory: {
    createPublicClient: vi.fn(() => ({
      search: mockSearch,
    })),
  },
}));

describe('useNewsSearch', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.history.replaceState({}, '', '/lt/naujienos');

    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        app: { locale: 'lt' },
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
    vi.restoreAllMocks();
  });

  it('initializes with default values and provided initialNews', async () => {
    const { useNewsSearch } = await import('../useNewsSearch');
    const search = useNewsSearch({
      initialNews: [
        {
          id: 1,
          title: 'Initial News',
          short: 'Initial excerpt',
          permalink: 'initial-news',
          image: '/test.jpg',
          publish_time: '2026-09-01T12:00:00.000Z',
          lang: 'lt',
          category: 'Atstovavimas',
        },
      ],
      initialTotal: 10,
    });

    expect(search.query.value).toBe('');
    expect(search.selectedCategories.value).toEqual([]);
    expect(search.selectedTenants.value).toEqual([]);
    expect(search.selectedYears.value).toEqual([]);
    expect(search.selectedTags.value).toEqual([]);
    expect(search.news.value.length).toBe(1);
    expect(search.totalHits.value).toBe(10);
    expect(search.news.value[0].title).toBe('Initial News');
  });

  it('initializes from URL query parameters if present', async () => {
    window.history.replaceState({}, '', '/lt/naujienos?search=konferencija&category=Atstovavimas&tenant=VU%20SA&year=2026&tag=Studijos&sort=date_asc');

    const { useNewsSearch } = await import('../useNewsSearch');
    const search = useNewsSearch();

    expect(search.query.value).toBe('konferencija');
    expect(search.selectedCategories.value).toEqual(['Atstovavimas']);
    expect(search.selectedTenants.value).toEqual(['VU SA']);
    expect(search.selectedYears.value).toEqual(['2026']);
    expect(search.selectedTags.value).toEqual(['Studijos']);
    expect(search.sortBy.value).toBe('date_asc');
    expect(search.hasActiveFilters.value).toBe(true);
    expect(search.activeFilterCount.value).toBe(4);
  });

  it('toggles and sets filters properly', async () => {
    const { useNewsSearch } = await import('../useNewsSearch');
    const search = useNewsSearch();

    search.toggleCategory('Atstovavimas');
    expect(search.selectedCategories.value).toEqual(['Atstovavimas']);
    search.toggleCategory('Atstovavimas');
    expect(search.selectedCategories.value).toEqual([]);

    search.setCategory('Renginiai');
    expect(search.selectedCategories.value).toEqual(['Renginiai']);
    search.setCategory(null);
    expect(search.selectedCategories.value).toEqual([]);

    search.toggleTenant('VU SA MIF');
    expect(search.selectedTenants.value).toEqual(['VU SA MIF']);

    search.toggleYear(2025);
    expect(search.selectedYears.value).toEqual(['2025']);

    search.toggleTag('Studijos');
    expect(search.selectedTags.value).toEqual(['Studijos']);

    search.clearFilters();
    expect(search.selectedCategories.value).toEqual([]);
    expect(search.selectedTenants.value).toEqual([]);
    expect(search.selectedYears.value).toEqual([]);
    expect(search.selectedTags.value).toEqual([]);
    expect(search.query.value).toBe('');
  });

  it('performs search and maps documents to NewsItems correctly', async () => {
    const { useNewsSearch } = await import('../useNewsSearch');
    const search = useNewsSearch();

    await search.performSearch(false);

    expect(mockSearch).toHaveBeenCalled();
    expect(search.news.value.length).toBe(1);
    expect(search.news.value[0].title).toBe('VU SA Konferencija');
    expect(search.news.value[0].category).toBe('Atstovavimas');
    expect(search.categoryFacets.value).toEqual([{ label: 'Atstovavimas', value: 'Atstovavimas', count: 1 }]);
    expect(search.tenantFacets.value).toEqual([{ label: 'VU SA', value: 'VU SA', count: 1 }]);
    expect(search.yearFacets.value).toEqual([{ label: '2026', value: '2026', count: 1 }]);
    expect(search.tagFacets.value).toEqual([{ label: 'Studijos', value: 'Studijos', count: 1 }]);
  });
});
