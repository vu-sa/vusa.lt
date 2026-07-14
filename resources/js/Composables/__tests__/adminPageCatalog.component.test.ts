import { describe, it, expect, vi, beforeEach } from 'vitest';
import { defineComponent } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { LayoutDashboard } from 'lucide-vue-next';

import { createMockPage } from '@/tests/helpers/createMockPage';
import {
  ADMIN_PAGE_CATALOG,
  resolveCatalogEntryByRoute,
  resolvePageIcon,
  useAdminPageCatalog,
} from '@/Composables/adminPageCatalog';
import {
  InstitutionIcon,
  MeetingIcon,
  NewsIcon,
  PageIcon,
  UserIcon,
} from '@/Components/icons';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

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

describe('useAdminPageCatalog permission filtering', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({ auth: { can: { read: { meeting: true }, create: { meeting: false } } } }) as any,
    );
  });

  it('includes always-available pages and permitted ones, excludes the rest', () => {
    const Harness = defineComponent({
      setup() {
        const { catalog } = useAdminPageCatalog();
        return { catalog };
      },
      template: '<div />',
    });

    const wrapper = mount(Harness);
    const ids = (wrapper.vm as any).catalog.map((e: any) => e.id);

    // No `can` predicate → always present
    expect(ids).toContain('nav-dashboard');
    expect(ids).toContain('nav-profile');
    // read.meeting === true
    expect(ids).toContain('nav-meetings');
    // read.institution undefined → filtered out
    expect(ids).not.toContain('nav-institutions');
    // create.meeting falsy → filtered out
    expect(ids).not.toContain('create-meeting');
  });
});
