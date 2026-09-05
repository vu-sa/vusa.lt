import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';

const mockSearch = vi.fn().mockResolvedValue({
  hits: [
    {
      document: {
        id: '1',
        title: 'VU SA Konferencija',
        date: Math.floor(Date.now() / 1000) + 86400,
        end_date: null,
        is_all_day: false,
        is_remote: false,
        location: 'Vilnius',
        category_name: 'Konferencija',
        tenant_shortname: 'VU SA',
        url: '/lt/renginiai/1',
      },
    },
  ],
  found: 1,
  facet_counts: [
    {
      field_name: 'category_name',
      counts: [{ value: 'Konferencija', count: 1 }],
    },
    {
      field_name: 'tenant_shortname',
      counts: [{ value: 'VU SA', count: 1 }],
    },
    {
      field_name: 'year',
      counts: [{ value: '2026', count: 1 }],
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

describe('useCalendarSearch', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.history.replaceState({}, '', '/lt/renginiai');

    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        typesenseConfig: {
          apiKey: 'test-key',
          nodes: [{ protocol: 'http', host: 'localhost', port: 8108 }],
          collections: {
            calendar: 'calendar',
          },
        },
      }),
    );
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('initializes with default values', async () => {
    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    expect(search.tab.value).toBe('upcoming');
    expect(search.query.value).toBe('');
    expect(search.selectedCategories.value).toEqual([]);
    expect(search.selectedTenants.value).toEqual([]);
    expect(search.selectedYears.value).toEqual([]);
    expect(search.isRemoteOnly.value).toBe(false);
  });

  it('initializes from URL query parameters if present', async () => {
    window.history.replaceState({}, '', '/lt/renginiai?tab=past&search=test&category=Konferencija&year=2026&remote=1');

    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    expect(search.tab.value).toBe('past');
    expect(search.query.value).toBe('test');
    expect(search.selectedCategories.value).toEqual(['Konferencija']);
    expect(search.selectedYears.value).toEqual(['2026']);
    expect(search.isRemoteOnly.value).toBe(true);
  });

  it('restores the selected tab and sort mode from a shared URL', async () => {
    window.history.replaceState({}, '', '/lt/renginiai?tab=all&year=2025&sort=date_asc');

    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    expect(search.tab.value).toBe('all');
    expect(search.selectedYears.value).toEqual(['2025']);
    expect(search.sortBy.value).toBe('date_asc');
  });

  it('toggles category selection', async () => {
    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    search.toggleCategory('Seminaras');
    expect(search.selectedCategories.value).toEqual(['Seminaras']);

    search.toggleCategory('Seminaras');
    expect(search.selectedCategories.value).toEqual([]);
  });

  it('toggles tenant selection', async () => {
    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    search.toggleTenant('MIF');
    expect(search.selectedTenants.value).toEqual(['MIF']);

    search.toggleTenant('MIF');
    expect(search.selectedTenants.value).toEqual([]);
  });

  it('keeps the active tab after selecting a year', async () => {
    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    search.toggleYear('2025');

    expect(search.tab.value).toBe('upcoming');
    expect(search.selectedYears.value).toEqual(['2025']);

    await search.performSearch(false);

    expect(mockSearch).toHaveBeenLastCalledWith(
      'calendar',
      expect.objectContaining({
        filter_by: expect.stringContaining('year:=[2025]'),
        sort_by: 'date:asc',
      }),
      expect.any(AbortSignal),
    );

    search.toggleYear('2025');
    expect(search.selectedYears.value).toEqual([]);
  });

  it('shows events from every date in the all tab', async () => {
    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    search.setTab('all');
    await search.performSearch(false);

    expect(mockSearch).toHaveBeenLastCalledWith(
      'calendar',
      expect.objectContaining({
        filter_by: '',
        sort_by: 'date:desc',
      }),
      expect.any(AbortSignal),
    );
  });

  it('sorts events by the selected date direction', async () => {
    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    search.setSortBy('date_desc');
    await search.performSearch(false);

    expect(mockSearch).toHaveBeenLastCalledWith(
      'calendar',
      expect.objectContaining({ sort_by: 'date:desc' }),
      expect.any(AbortSignal),
    );

    search.setSortBy('date_asc');
    await search.performSearch(false);

    expect(mockSearch).toHaveBeenLastCalledWith(
      'calendar',
      expect.objectContaining({ sort_by: 'date:asc' }),
      expect.any(AbortSignal),
    );
  });

  it('sorts matching events by relevance', async () => {
    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    search.query.value = 'konferencija';
    await search.performSearch(false);

    expect(mockSearch).toHaveBeenLastCalledWith(
      'calendar',
      expect.objectContaining({ sort_by: '_text_match:desc,date:asc' }),
      expect.any(AbortSignal),
    );
  });

  it('clears all active filters', async () => {
    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    search.query.value = 'abc';
    search.selectedCategories.value = ['Konferencija'];
    search.selectedTenants.value = ['MIF'];
    search.selectedYears.value = ['2025'];
    search.isRemoteOnly.value = true;

    expect(search.activeFilterCount.value).toBe(4);

    search.clearFilters();

    expect(search.query.value).toBe('');
    expect(search.selectedCategories.value).toEqual([]);
    expect(search.selectedTenants.value).toEqual([]);
    expect(search.selectedYears.value).toEqual([]);
    expect(search.isRemoteOnly.value).toBe(false);
    expect(search.activeFilterCount.value).toBe(0);
  });

  it('calls search on search execution and processes hits and facets', async () => {
    const { useCalendarSearch } = await import('../useCalendarSearch');
    const search = useCalendarSearch();

    await search.performSearch(false);

    expect(mockSearch).toHaveBeenCalled();
    expect(search.events.value).toHaveLength(1);
    expect(search.events.value[0].title).toBe('VU SA Konferencija');
    expect(search.categoryFacets.value).toEqual([
      { value: 'Konferencija', label: 'Konferencija', count: 1 },
    ]);
    expect(search.tenantFacets.value).toEqual([
      { value: 'VU SA', label: 'VU SA', count: 1 },
    ]);
    expect(search.yearFacets.value).toEqual([
      { value: '2026', label: '2026', count: 1 },
    ]);
  });
});
