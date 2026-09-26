import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import { useLocalCollectionSource, useTrashAwareSource, type CollectionSource } from '../useCollectionSource';

interface Row {
  id: number;
  name: string;
  degree: string;
}

const rows: Row[] = [
  { id: 1, name: 'Žurnalistika', degree: 'BA' },
  { id: 2, name: 'Informatika', degree: 'BA' },
  { id: 3, name: 'Informacinės sistemos', degree: 'MA' },
];

function makeSource(items: Row[] = rows) {
  return useLocalCollectionSource<Row>({
    items: ref(items),
    searchText: row => [row.name],
    defaultSort: 'name:asc',
    sortOptions: [
      { value: 'name:asc', label: 'A–Z', by: row => row.name },
      { value: 'name:desc', label: 'Z–A', by: row => row.name },
    ],
    facets: [{ field: 'degree', label: 'Laipsnis', get: row => row.degree }],
  });
}

describe('useLocalCollectionSource', () => {
  beforeEach(() => {
    localStorage.clear();
    window.history.replaceState({}, '', '/mano/study-programs');
  });

  it('searches without caring about Lithuanian diacritics or case', () => {
    const source = makeSource();

    source.search('ZURNAL');

    expect(source.items.value.map(row => row.id)).toEqual([1]);
    expect(source.total.value).toBe(1);
  });

  it('sorts with the Lithuanian collator and flips direction', () => {
    const source = makeSource();

    expect(source.items.value.map(row => row.name)).toEqual(['Informacinės sistemos', 'Informatika', 'Žurnalistika']);

    source.setSortBy('name:desc');

    expect(source.items.value[0].name).toBe('Žurnalistika');
  });

  it('counts each facet value as if that facet were not yet chosen', () => {
    const source = makeSource();

    source.toggleFilter('degree', 'MA');

    expect(source.items.value.map(row => row.id)).toEqual([3]);
    expect(source.facets.value[0].values).toEqual([
      { value: 'BA', label: 'BA', count: 2, isSelected: false },
      { value: 'MA', label: 'MA', count: 1, isSelected: true },
    ]);
    expect(source.chips.value).toEqual([{ id: 'degree:MA', label: 'Laipsnis: MA' }]);
  });

  it('keeps filters, sort and query in the URL and restores them', () => {
    const source = makeSource();
    source.toggleFilter('degree', 'BA');
    source.setSortBy('name:desc');
    source.search('info');

    expect(window.location.search).toBe('?search=info&sort=name%3Adesc&degree=BA');

    const restored = makeSource();

    expect(restored.query.value).toBe('info');
    expect(restored.sortBy.value).toBe('name:desc');
    expect(restored.items.value.map(row => row.id)).toEqual([2]);
  });

  it('shows 50 at a time and loads the next 50 on demand', () => {
    const many = Array.from({ length: 120 }, (_, index) => ({ id: index, name: `Programa ${String(index).padStart(3, '0')}`, degree: 'BA' }));
    const source = makeSource(many);

    expect(source.items.value).toHaveLength(50);
    expect(source.hasMore.value).toBe(true);

    source.loadMore();
    source.loadMore();

    expect(source.items.value).toHaveLength(120);
    expect(source.hasMore.value).toBe(false);
  });
});

describe('useTrashAwareSource', () => {
  it('only builds the source the page is showing', () => {
    const live = vi.fn(() => ({ kind: 'live' }) as unknown as CollectionSource<Row>);
    const trash = vi.fn(() => ({ kind: 'trash' }) as unknown as CollectionSource<Row>);

    window.history.replaceState({}, '', '/mano/pages');
    expect(useTrashAwareSource(live, trash)).toEqual({ kind: 'live' });

    window.history.replaceState({}, '', '/mano/pages?showDeleted=true');
    expect(useTrashAwareSource(live, trash)).toEqual({ kind: 'trash' });

    expect(live).toHaveBeenCalledTimes(1);
    expect(trash).toHaveBeenCalledTimes(1);
  });
});
