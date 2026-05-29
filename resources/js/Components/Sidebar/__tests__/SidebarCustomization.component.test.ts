import { describe, it, expect, vi, beforeEach } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import { createUIPreferencesProvider } from '@/Composables/useUIPreferences';
import SidebarCustomizeDialog from '@/Components/Sidebar/SidebarCustomizeDialog.vue';
import QuickActionSettingsPopover from '@/Components/Sidebar/QuickActionSettingsPopover.vue';
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

// Passthrough stubs for the reka-ui DropdownMenu primitives so the popover's
// content renders inline (reka Menu relies on teleport + pointer events that
// jsdom doesn't model). The checkbox item stub deliberately mirrors reka-ui's
// REAL public contract — `modelValue` in, `update:modelValue` out — so the
// test fails if the component reverts to the `:checked` / `@update:checked`
// binding (which reka-ui doesn't speak).
const dropdownStubs = {
  DropdownMenu: { template: '<div><slot /></div>' },
  DropdownMenuTrigger: { template: '<div><slot /></div>' },
  DropdownMenuContent: { template: '<div><slot /></div>' },
  DropdownMenuLabel: { template: '<div><slot /></div>' },
  DropdownMenuSeparator: { template: '<hr />' },
  DropdownMenuCheckboxItem: {
    props: ['id', 'modelValue'],
    emits: ['update:modelValue'],
    template: `<div :id="id" :data-checked="String(modelValue)"
        role="menuitemcheckbox"
        @click="$emit('update:modelValue', !modelValue)"><slot /></div>`,
  },
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

    const toggle = wrapper.find('#section-quick_actions');
    expect(toggle.exists()).toBe(true);

    await toggle.trigger('click');

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, options] = fetchMock.mock.calls[0];
    expect(url as string).toContain('profile.updateUIPreferences');
    const body = JSON.parse((options as RequestInit).body as string);
    expect(body.sidebar.sections.quick_actions).toBe(false);
  });

  it('renders a draggable row with a toggle for every toggleable section', () => {
    const wrapper = mount(harness(defineComponent({
      components: { SidebarCustomizeDialog },
      template: '<SidebarCustomizeDialog :open="true" />',
    })), {
      global: { stubs: { ...commonStubs } },
    });

    // Six toggleable sections, each with its own Switch.
    const sections = ['quick_actions', 'recently_visited', 'followed_institutions', 'spacer', 'start_fm', 'secondary'];
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
              sidebar: { sections: { quick_actions: false }, order: ['secondary', 'quick_actions'] },
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
    expect(body.sidebar.sections.quick_actions).toBe(true);
    expect(body.sidebar.order[0]).toBe('pinned');
  });
});

describe('QuickActionSettingsPopover', () => {
  beforeEach(() => {
    // Enable several quick actions and pre-hide one so we can assert the
    // checkbox reflects visibility through `model-value`.
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        auth: {
          can: { create: { meeting: true, news: true, reservation: true } },
          user: { ui_preferences: { quick_actions: { new_news: false } } },
        },
      }) as any,
    );
  });

  it('binds each item to its visibility via model-value', () => {
    const wrapper = mount(harness(defineComponent({
      components: { QuickActionSettingsPopover },
      template: '<QuickActionSettingsPopover />',
    })), {
      global: { stubs: { ...commonStubs, ...dropdownStubs } },
    });

    // new_meeting is visible (default true), new_news was hidden.
    expect(wrapper.find('#popover-qa-new_meeting').attributes('data-checked')).toBe('true');
    expect(wrapper.find('#popover-qa-new_news').attributes('data-checked')).toBe('false');
  });

  it('toggling an item persists via fetch with the new value', async () => {
    const fetchMock = vi.mocked(globalThis.fetch);
    const wrapper = mount(harness(defineComponent({
      components: { QuickActionSettingsPopover },
      template: '<QuickActionSettingsPopover />',
    })), {
      global: { stubs: { ...commonStubs, ...dropdownStubs } },
    });

    const item = wrapper.find('#popover-qa-new_meeting');
    expect(item.exists()).toBe(true);

    await item.trigger('click');

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, options] = fetchMock.mock.calls[0];
    expect(url as string).toContain('profile.updateUIPreferences');
    const body = JSON.parse((options as RequestInit).body as string);
    // new_meeting started visible (true) → toggled to false.
    expect(body.quick_actions.new_meeting).toBe(false);
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
    expect(url as string).toContain('profile.updateUIPreferences');
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
    expect(url).toContain('profile.updateUIPreferences');
    expect(body.appearance.density).toBe('compact');
  });
});
