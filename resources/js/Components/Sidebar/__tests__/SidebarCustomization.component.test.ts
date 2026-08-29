import { describe, it, expect, vi, beforeEach } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import { createUIPreferencesProvider } from '@/Composables/useUIPreferences';
import SidebarCustomizeDialog from '@/Components/Sidebar/SidebarCustomizeDialog.vue';
import RecentlyVisitedSection from '@/Components/Sidebar/RecentlyVisitedSection.vue';
import PinnedPagesSection from '@/Components/Sidebar/PinnedPagesSection.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

// Passthrough stubs for the shadcn sidebar primitives (they need a
// SidebarProvider context that is irrelevant to what we assert here).
const sidebarStubs = {
  SidebarGroup: { template: '<div><slot /></div>' },
  SidebarGroupContent: { template: '<div><slot /></div>' },
  SidebarGroupLabel: { template: '<div><slot /></div>' },
  SidebarMenu: { template: '<ul><slot /></ul>' },
  SidebarMenuItem: { template: '<li><slot /></li>' },
  SidebarMenuButton: { template: '<div><slot /></div>' },
  SidebarMenuAction: { template: '<button class="menu-action"><slot /></button>' },
  Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
};

function harness(child: ReturnType<typeof defineComponent>) {
  return defineComponent({
    setup() {
      createUIPreferencesProvider();
      return () => h(child);
    },
  });
}

beforeEach(() => {
  vi.mocked(globalThis.fetch).mockClear();
});

describe('SidebarCustomizeDialog', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage() as any);
  });

  it('toggling a section flips its state and persists via fetch (not Inertia)', async () => {
    const fetchMock = vi.mocked(globalThis.fetch);
    const wrapper = mount(harness(defineComponent({
      components: { SidebarCustomizeDialog },
      template: '<SidebarCustomizeDialog :open="true" />',
    })), {
      global: { stubs: { ...commonStubs } },
    });

    const toggle = wrapper.find('#section-followed_institutions');
    expect(toggle.exists()).toBe(true);

    await toggle.trigger('click');

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, options] = fetchMock.mock.calls[0];
    expect(url as string).toContain('api.v1.admin.user-preferences.update');
    const body = JSON.parse((options as RequestInit).body as string);
    expect(body.sidebar.sections.followed_institutions).toBe(false);
  });

  it('renders a draggable row with a toggle for every toggleable section', () => {
    const wrapper = mount(harness(defineComponent({
      components: { SidebarCustomizeDialog },
      template: '<SidebarCustomizeDialog :open="true" />',
    })), {
      global: { stubs: { ...commonStubs } },
    });

    // Every toggleable section gets its own Switch. "Greiti veiksmai" is no longer one
    // of them — the action window replaced that sidebar list.
    const sections = ['pinned', 'recently_visited', 'followed_institutions', 'spacer', 'start_fm', 'secondary'];
    for (const key of sections) {
      expect(wrapper.find(`#section-${key}`).exists()).toBe(true);
    }
  });

  it('reset persists an all-visible map and the default order', async () => {
    // Seed a hidden section so reset has something to restore.
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        auth: {
          user: {
            ui_preferences: {
              sidebar: { sections: { followed_institutions: false }, order: ['secondary', 'followed_institutions'] },
            },
          },
        },
      }) as any,
    );

    const fetchMock = vi.mocked(globalThis.fetch);
    const wrapper = mount(harness(defineComponent({
      components: { SidebarCustomizeDialog },
      template: '<SidebarCustomizeDialog :open="true" />',
    })), {
      global: { stubs: { ...commonStubs } },
    });

    const resetButton = wrapper.findAll('button').find(b => b.text().includes('Atstatyti'));
    expect(resetButton).toBeTruthy();
    await resetButton!.trigger('click');

    const lastCall = fetchMock.mock.calls[fetchMock.mock.calls.length - 1];
    const body = JSON.parse((lastCall[1] as RequestInit).body as string);
    expect(body.sidebar.sections.followed_institutions).toBe(true);
    expect(body.sidebar.order[0]).toBe('pinned');
  });
});

describe('RecentlyVisitedSection', () => {
  it('renders every recent page, with a fallback title for non-catalogued routes', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        auth: {
          user: {
            ui_preferences: {
              recent_pages: [
                { route: 'meetings.index', params: {}, visited_at: '2026-05-19T10:00:00Z' },
                { route: 'users.edit', params: { user: 1 }, title: 'Jonas Jonaitis', url: '/mano/users/1/edit', visited_at: '2026-05-19T09:00:00Z' },
              ],
            },
          },
        },
      }) as any,
    );

    const wrapper = mount(harness(defineComponent({
      components: { RecentlyVisitedSection },
      template: '<RecentlyVisitedSection />',
    })), {
      global: { stubs: { ...commonStubs, ...sidebarStubs, RecentPagesDialog: { template: '<div />' } } },
    });

    const links = wrapper.findAll('a');
    expect(links).toHaveLength(2);
    expect(links[0].text()).toContain('Posėdžiai');
    expect(links[1].text()).toContain('Jonas Jonaitis');
  });

  it('shows empty-state hint when there are no recent pages', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage() as any);

    const wrapper = mount(harness(defineComponent({
      components: { RecentlyVisitedSection },
      template: '<RecentlyVisitedSection />',
    })), {
      global: { stubs: { ...commonStubs, ...sidebarStubs, RecentPagesDialog: { template: '<div />' } } },
    });

    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.text()).toContain('Čia bus rodomi neseniai aplankyti puslapiai');
  });

  it('the per-row star pins the page and persists pinned_pages', async () => {
    const fetchMock = vi.mocked(globalThis.fetch);
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        auth: {
          user: {
            ui_preferences: {
              recent_pages: [
                { route: 'users.edit', params: { user: 1 }, title: 'Jonas', url: '/mano/users/1/edit', visited_at: '2026-05-19T10:00:00Z' },
              ],
            },
          },
        },
      }) as any,
    );

    const wrapper = mount(harness(defineComponent({
      components: { RecentlyVisitedSection },
      template: '<RecentlyVisitedSection />',
    })), {
      global: { stubs: { ...commonStubs, ...sidebarStubs, RecentPagesDialog: { template: '<div />' } } },
    });

    await wrapper.find('button.menu-action').trigger('click');

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, options] = fetchMock.mock.calls[0];
    expect(url as string).toContain('api.v1.admin.user-preferences.update');
    const body = JSON.parse((options as RequestInit).body as string);
    expect(body.pinned_pages[0].url).toBe('/mano/users/1/edit');
  });
});

describe('PinnedPagesSection', () => {
  it('renders each pinned page as a link', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        auth: {
          user: {
            ui_preferences: {
              pinned_pages: [
                { route: 'users.index', params: {}, title: 'Users', url: '/mano/users' },
                { route: 'news.index', params: {}, title: 'News', url: '/mano/news' },
              ],
            },
          },
        },
      }) as any,
    );

    const wrapper = mount(harness(defineComponent({
      components: { PinnedPagesSection },
      template: '<PinnedPagesSection />',
    })), {
      global: { stubs: { ...commonStubs, ...sidebarStubs } },
    });

    const links = wrapper.findAll('a');
    expect(links).toHaveLength(2);
    expect(links[0].text()).toContain('Users');
  });

  it('renders nothing when there are no pinned pages', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage() as any);

    const wrapper = mount(harness(defineComponent({
      components: { PinnedPagesSection },
      template: '<PinnedPagesSection />',
    })), {
      global: { stubs: { ...commonStubs, ...sidebarStubs } },
    });

    expect(wrapper.find('a').exists()).toBe(false);
  });
});

describe('density toggle', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage() as any);
  });

  it('toggling compact view persists appearance.density', async () => {
    const fetchMock = vi.mocked(globalThis.fetch);
    const wrapper = mount(harness(defineComponent({
      components: { SidebarCustomizeDialog },
      template: '<SidebarCustomizeDialog :open="true" />',
    })), {
      global: { stubs: { ...commonStubs } },
    });

    const toggle = wrapper.find('#density-compact');
    expect(toggle.exists()).toBe(true);

    await toggle.trigger('click');

    const lastCall = fetchMock.mock.calls[fetchMock.mock.calls.length - 1];
    const url = lastCall[0] as string;
    const body = JSON.parse((lastCall[1] as RequestInit).body as string);
    expect(url).toContain('api.v1.admin.user-preferences.update');
    expect(body.appearance.density).toBe('compact');
  });
});
