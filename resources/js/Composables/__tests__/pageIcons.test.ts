import { describe, it, expect } from 'vitest';
import { Home, LayoutDashboard } from '@lucide/vue';

import { resolvePageIcon } from '@/Composables/pageIcons';
import {
  InstitutionIcon,
  MeetingIcon,
  NewsIcon,
  PageIcon,
  UserIcon,
} from '@/Components/icons';

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

  it('resolves an index route by its prefix, and Pradžia by name', () => {
    expect(resolvePageIcon('meetings.index')).toBe(MeetingIcon);
    expect(resolvePageIcon('dashboard', '/mano')).toBe(Home);
  });

  it('falls back to a generic page icon for unknown paths/prefixes or empty input', () => {
    expect(resolvePageIcon('page', '/mano/unknownthing')).toBe(PageIcon);
    expect(resolvePageIcon('whatever.random')).toBe(PageIcon);
    expect(resolvePageIcon(undefined)).toBe(PageIcon);
  });
});
