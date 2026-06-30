import { describe, it, expect, vi, beforeEach } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import { createUIPreferencesProvider } from '@/Composables/useUIPreferences';
import NavQuickActions from '@/Components/NavQuickActions.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const sidebarStubs = {
  SidebarGroup: { template: '<div data-testid="sidebar-group"><slot /></div>' },
  SidebarGroupContent: { template: '<div><slot /></div>' },
  SidebarGroupLabel: { template: '<div><slot /></div>' },
  SidebarMenu: { template: '<ul><slot /></ul>' },
  SidebarMenuItem: { template: '<li><slot /></li>' },
  SidebarMenuButton: { template: '<div><slot /></div>' },
};

function harness(child: ReturnType<typeof defineComponent>) {
  return defineComponent({
    setup() {
      createUIPreferencesProvider();
      return () => h(child);
    },
  });
}

describe('NavQuickActions', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        auth: {
          can: { create: { meeting: true, news: true, reservation: true } },
          user: { ui_preferences: {} },
        },
      }) as any,
    );
  });

  it('renders visible quick actions', () => {
    const wrapper = mount(harness(defineComponent({
      components: { NavQuickActions },
      template: '<NavQuickActions />',
    })), {
      global: { stubs: { ...commonStubs, ...sidebarStubs } },
    });

    expect(wrapper.find('[data-testid="sidebar-group"]').exists()).toBe(true);
    expect(wrapper.findAll('li').length).toBeGreaterThan(0);
  });

  it('shows empty state and keeps settings gear when all quick actions are hidden', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        auth: {
          can: { create: { meeting: true, news: true, reservation: true } },
          user: {
            ui_preferences: {
              quick_actions: {
                new_meeting: false,
                new_news: false,
                new_reservation: false,
              },
            },
          },
        },
      }) as any,
    );

    const wrapper = mount(harness(defineComponent({
      components: { NavQuickActions },
      template: '<NavQuickActions />',
    })), {
      global: { stubs: { ...commonStubs, ...sidebarStubs } },
    });

    expect(wrapper.find('[data-testid="sidebar-group"]').exists()).toBe(true);
    expect(wrapper.findAll('li').length).toBe(0);
    expect(wrapper.text()).toContain('Išjungti greitus veiksmus?');

    const btn = wrapper.find('button[type="button"]');
    expect(btn.exists()).toBe(true);
  });
});
