import { describe, it, expect, vi, beforeEach } from 'vitest';
import { defineComponent } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { createUIPreferencesProvider } from '@/Composables/useUIPreferences';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const fetchMock = vi.mocked(globalThis.fetch);

function lastFetch() {
  const call = fetchMock.mock.calls[fetchMock.mock.calls.length - 1];
  return {
    url: call[0] as string,
    body: JSON.parse((call[1] as RequestInit).body as string),
  };
}

function mountProvider() {
  const Harness = defineComponent({
    setup() {
      const ctx = createUIPreferencesProvider();
      return { ctx };
    },
    template: '<div />',
  });
  const wrapper = mount(Harness);
  return (wrapper.vm as any).ctx as ReturnType<typeof createUIPreferencesProvider>;
}

beforeEach(() => {
  fetchMock.mockClear();
  vi.mocked(usePage).mockReturnValue(
    createMockPage({
      csrf_token: 'test-token',
      auth: {
        user: {
          ui_preferences: {
            recent_pages: [
              { route: 'meetings.index', params: {}, visited_at: '2026-05-19T10:00:00Z' },
              { route: 'gone.route', params: {}, visited_at: '2026-05-19T09:00:00Z' },
            ],
          },
        },
      },
    }) as any,
  );
});

describe('recentPages', () => {
  it('maps every stored page (catalogued or not) to a navigable item', () => {
    const ctx = mountProvider();
    const recent = ctx.recentPages.value;

    expect(recent).toHaveLength(2);
    expect(recent[0].routeName).toBe('meetings.index');
    expect(recent[0].type).toBe('page');
    expect(recent[0].href).toContain('meetings.index');
    expect(recent[0].id).toBe(recent[0].href); // identity is the path
    // Non-catalogued route is still kept, with a prettified fallback title.
    expect(recent[1].routeName).toBe('gone.route');
    expect(recent[1].title).toBe('Gone route');
  });

  it('prefers the stored title over the catalog label', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        auth: {
          user: {
            ui_preferences: {
              recent_pages: [
                { route: 'users.edit', params: { user: 1 }, title: 'Jonas Jonaitis', url: '/mano/users/1/edit', visited_at: '2026-05-19T10:00:00Z' },
              ],
            },
          },
        },
      }) as any,
    );
    const ctx = mountProvider();
    const recent = ctx.recentPages.value;
    expect(recent[0].title).toBe('Jonas Jonaitis');
    expect(recent[0].href).toBe('/mano/users/1/edit');
  });
});

describe('trackVisit', () => {
  it('optimistically records and persists any admin route immediately', () => {
    const ctx = mountProvider();
    fetchMock.mockClear();

    ctx.trackVisit('users.create', {}, 'New user', '/mano/users/create');

    expect(ctx.recentPages.value[0].id).toBe('/mano/users/create');
    expect(ctx.recentPages.value[0].routeName).toBe('users.create');
    expect(ctx.recentPages.value[0].title).toBe('New user');
    expect(fetchMock).toHaveBeenCalledTimes(1);
    const { url, body } = lastFetch();
    expect(url).toContain('api.v1.admin.user-preferences.trackRecentPage');
    expect(body.title).toBe('New user');
  });

  it('treats the same path as one entry regardless of query string', () => {
    const ctx = mountProvider();

    ctx.trackVisit('users.index', {}, 'Users', '/mano/users');
    ctx.trackVisit('users.index', { page: 2 }, 'Users', '/mano/users');

    const usersEntries = ctx.recentPages.value.filter(p => p.href === '/mano/users');
    expect(usersEntries).toHaveLength(1);
  });

  it('ignores an empty route name', () => {
    const ctx = mountProvider();
    fetchMock.mockClear();

    ctx.trackVisit('', {});

    expect(fetchMock).not.toHaveBeenCalled();
  });
});

describe('pinned pages', () => {
  it('maps stored pinned pages to navigable items', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        auth: {
          user: {
            ui_preferences: {
              pinned_pages: [
                { route: 'users.index', params: {}, title: 'Users', url: '/mano/users' },
              ],
            },
          },
        },
      }) as any,
    );
    const ctx = mountProvider();
    expect(ctx.pinnedPages.value).toHaveLength(1);
    expect(ctx.pinnedPages.value[0].href).toBe('/mano/users');
    expect(ctx.isPinned({ routeName: 'users.index', href: '/mano/users' })).toBe(true);
    expect(ctx.isPinned({ routeName: 'news.index', href: '/mano/news' })).toBe(false);
  });

  it('togglePin adds, then removes, persisting each change via pinned_pages', () => {
    const ctx = mountProvider();
    fetchMock.mockClear();

    ctx.togglePin({ routeName: 'news.index', href: '/mano/news', title: 'News' });
    expect(ctx.isPinned({ routeName: 'news.index', href: '/mano/news' })).toBe(true);
    let { url, body } = lastFetch();
    expect(url).toContain('api.v1.admin.user-preferences.update');
    expect(body.pinned_pages[0].url).toBe('/mano/news');

    ctx.togglePin({ routeName: 'news.index', href: '/mano/news', title: 'News' });
    expect(ctx.isPinned({ routeName: 'news.index', href: '/mano/news' })).toBe(false);
    ({ body } = lastFetch());
    expect(body.pinned_pages).toHaveLength(0);
  });
});
