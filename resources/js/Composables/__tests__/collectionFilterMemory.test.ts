import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useCollectionFilterMemory } from '@/Composables/collectionFilterMemory';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const visit = (path: string) => window.history.replaceState({}, '', path);
const search = () => new URLSearchParams(window.location.search);

describe('useCollectionFilterMemory', () => {
  beforeEach(() => {
    localStorage.clear();
    visit('/mano/reservations');
  });

  it('puts the remembered filters back into a bare URL, keeping the page-owned keys', () => {
    visit('/mano/reservations?state=created&sort=start_time:desc&search=x');
    useCollectionFilterMemory(['state', 'sort']).remember();

    visit('/mano/reservations?view=table');
    const result = useCollectionFilterMemory(['state', 'sort']).restore();

    expect(result).toEqual({ decided: true, restored: true });
    expect(search().get('state')).toBe('created');
    expect(search().get('sort')).toBe('start_time:desc');
    expect(search().get('view')).toBe('table');
    // The search text is not a filter; it is not remembered.
    expect(search().has('search')).toBe(false);
  });

  it('lets a URL with its own filters or a search win', () => {
    visit('/mano/reservations?state=created');
    useCollectionFilterMemory(['state']).remember();

    visit('/mano/reservations?search=projektorius');
    expect(useCollectionFilterMemory(['state']).restore()).toEqual({ decided: true, restored: false });
    expect(search().has('state')).toBe(false);
  });

  it('reports a first visit as undecided, so the page may apply its defaults', () => {
    expect(useCollectionFilterMemory(['state']).restore()).toEqual({ decided: false, restored: false });
  });

  it('remembers a cleared list as decided, so the defaults do not come back', () => {
    useCollectionFilterMemory(['state']).remember();

    expect(useCollectionFilterMemory(['state']).restore()).toEqual({ decided: true, restored: false });
  });

  it('keeps each page apart and leaves the trash view out', () => {
    visit('/mano/reservations?state=created');
    useCollectionFilterMemory(['state']).remember();

    visit('/mano/resources');
    expect(useCollectionFilterMemory(['state']).restore().restored).toBe(false);

    visit('/mano/reservations?showDeleted=true');
    expect(useCollectionFilterMemory(['state']).restore().restored).toBe(false);
    expect(search().has('state')).toBe(false);
  });
});
