import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import { useCollectionView } from '../useCollectionView';

const media = vi.hoisted(() => ({ md: true, xl: true }));

vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal<Record<string, unknown>>();
  const { computed } = await import('vue');

  return {
    ...actual,
    useMediaQuery: (query: string) => computed(() => (query.includes('768') ? media.md : media.xl)),
  };
});

const options = { collection: 'meetings', defaultView: 'rows' as const };

beforeEach(() => {
  media.md = true;
  media.xl = true;
  localStorage.clear();
  window.history.replaceState({}, '', '/mano/meetings');
});

describe('useCollectionView', () => {
  it('starts on the entity default', () => {
    expect(useCollectionView(options).view.value).toBe('rows');
  });

  it('does not freeze the default into storage, so an entity can change it later', () => {
    useCollectionView(options);
    expect(localStorage.getItem('admin-collection-view:1:meetings')).toBeNull();

    expect(useCollectionView({ ...options, defaultView: 'table' }).view.value).toBe('table');
  });

  it('lets the URL win over everything (U1)', () => {
    window.history.replaceState({}, '', '/mano/meetings?view=table');
    localStorage.setItem('admin-collection-view:1:meetings', 'rows');

    expect(useCollectionView(options).view.value).toBe('table');
  });

  it('remembers the last choice per collection, and not for another one', async () => {
    useCollectionView(options).setView('table');
    await nextTick();

    expect(useCollectionView(options).view.value).toBe('table');

    // Another collection, on its own page (so without this one's ?view=).
    window.history.replaceState({}, '', '/mano/institutions');
    expect(useCollectionView({ ...options, collection: 'institutions' }).view.value).toBe('rows');
  });

  it('ignores a view name it does not know', () => {
    window.history.replaceState({}, '', '/mano/meetings?view=kanban');

    expect(useCollectionView(options).view.value).toBe('rows');
  });

  it('offers only what the viewport can show: rows below md, no preview below xl', () => {
    media.md = false;
    expect(useCollectionView(options).availableViews.value).toEqual(['rows']);

    media.md = true;
    media.xl = false;
    expect(useCollectionView(options).availableViews.value).toEqual(['rows', 'table']);

    media.xl = true;
    expect(useCollectionView(options).availableViews.value).toEqual(['rows', 'table', 'preview']);
  });

  it('falls back to rows when the chosen view does not fit — a saved table on a phone', () => {
    localStorage.setItem('admin-collection-view:1:meetings', 'table');
    media.md = false;

    expect(useCollectionView(options).view.value).toBe('rows');
  });

  it('keeps a non-default view in the URL and removes the default one', () => {
    const { setView } = useCollectionView(options);

    setView('preview');
    expect(new URLSearchParams(window.location.search).get('view')).toBe('preview');

    setView('rows');
    expect(new URLSearchParams(window.location.search).has('view')).toBe(false);
  });

  it('does not drop other query parameters when it writes the view', () => {
    window.history.replaceState({}, '', '/mano/meetings?q=senatas&year=2026');

    useCollectionView(options).setView('table');

    const params = new URLSearchParams(window.location.search);
    expect(params.get('q')).toBe('senatas');
    expect(params.get('year')).toBe('2026');
    expect(params.get('view')).toBe('table');
  });

  it('offers cards only to a collection that opts in, on phones too', () => {
    expect(useCollectionView(options).availableViews.value).not.toContain('cards');

    const browsing = { collection: 'resources', defaultView: 'cards' as const, views: ['cards', 'rows', 'table'] as const };
    expect(useCollectionView({ ...browsing, views: [...browsing.views] }).view.value).toBe('cards');

    media.md = false;
    expect(useCollectionView({ ...browsing, views: [...browsing.views] }).availableViews.value).toEqual(['cards', 'rows']);
  });
});
