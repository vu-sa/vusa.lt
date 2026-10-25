import { describe, it, expect } from 'vitest';
import { LayoutDashboard } from '@lucide/vue';

import {
  ADMIN_PAGE_CATALOG,
  resolveCatalogEntryByRoute,
  resolvePageIcon,
} from '@/Composables/adminPageCatalog';
import {
  InstitutionIcon,
  MeetingIcon,
  NewsIcon,
  PageIcon,
  UserIcon,
} from '@/Components/icons';

describe('resolveCatalogEntryByRoute', () => {
  it('resolves a known catalog route', () => {
    expect(resolveCatalogEntryByRoute('meetings.index')?.id).toBe('nav-meetings');
  });

  it('returns undefined for unknown or empty routes', () => {
    expect(resolveCatalogEntryByRoute('something.random')).toBeUndefined();
    expect(resolveCatalogEntryByRoute(undefined)).toBeUndefined();
  });

  it('every catalog entry has a unique route name and id', () => {
    const routes = ADMIN_PAGE_CATALOG.map(e => e.routeName);
    const ids = ADMIN_PAGE_CATALOG.map(e => e.id);
    expect(new Set(routes).size).toBe(routes.length);
    expect(new Set(ids).size).toBe(ids.length);
  });
});

describe('resolvePageIcon', () => {
  // Admin pages are served via a generic `page` route, so the URL is the reliable
  // signal — every stored recent/pinned entry has routeName === 'page'.
  it('resolves the resource icon from the URL path (the reliable signal)', () => {
    expect(resolvePageIcon('page', '/mano/institutions/123')).toBe(InstitutionIcon);
    expect(resolvePageIcon('page', '/mano/news/create')).toBe(NewsIcon);
    expect(resolvePageIcon('page', '/mano/users/1/edit')).toBe(UserIcon);
    expect(resolvePageIcon('page', '/mano/dashboard/atstovavimas')).toBe(LayoutDashboard);
  });

  it('falls back to the route name when the URL resolves nothing', () => {
    // institutions.show is not a catalog entry, but its prefix maps to the model icon.
    expect(resolvePageIcon('institutions.show')).toBe(InstitutionIcon);
    expect(resolvePageIcon('users.edit')).toBe(UserIcon);
  });

  it('prefers the curated catalog icon when the route is catalogued', () => {
    expect(resolvePageIcon('meetings.index')).toBe(MeetingIcon);
  });

  it('falls back to a generic page icon for unknown paths/prefixes or empty input', () => {
    expect(resolvePageIcon('page', '/mano/unknownthing')).toBe(PageIcon);
    expect(resolvePageIcon('whatever.random')).toBe(PageIcon);
    expect(resolvePageIcon(undefined)).toBe(PageIcon);
  });
});
