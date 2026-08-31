import { describe, it, expect, beforeAll, beforeEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { usePage } from '@inertiajs/vue3';

import ActionWindow from '@/Components/ActionWindow/ActionWindow.vue';
import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

/**
 * Mounting is the point: the screens are async components resolved through a
 * registry, so a bad import path or a setup-time crash in any of them is
 * invisible to the type checker and to a unit test of the composable.
 *
 * Not covered on purpose: which of Drawer / Dialog actually renders. That
 * depends on a live media query inside SidebarProvider and on vaul's DOM
 * behaviour, neither of which jsdom models — outside a provider the component
 * takes the desktop branch, which is what these tests exercise.
 */

const ALL_PERMISSIONS = {
  create: { meeting: true, problem: true, reservation: true, duty: true },
  manageSettings: true,
};

const mountWindow = (can: Record<string, unknown> = ALL_PERMISSIONS) => {
  vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can } }));

  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      return () => h(ActionWindow);
    },
  }), {
    global: {
      stubs: {
        ...commonStubs,
        // The real Drawer portals through vaul, which needs a layout engine.
        Drawer: { template: '<div v-if="open"><slot /></div>', props: ['open'] },
        DrawerContent: { template: '<div><slot /></div>' },
        DrawerTitle: { template: '<div><slot /></div>' },
      },
    },
  });

  return { wrapper, window };
};

/**
 * Screens are async components. Warming the module registry first means their
 * dynamic import resolves in a microtask, so a flush is enough; the polling is
 * a safety net for a cold module under a loaded CI box, and throws rather than
 * leaving a later assertion to fail as a mysterious empty render.
 */
const settle = async (wrapper: ReturnType<typeof mount>) => {
  for (let attempt = 0; attempt < 50; attempt++) {
    await flushPromises();

    if (wrapper.find('[data-slot="action-window-screen"]').exists()) {
      return;
    }

    await new Promise(resolve => setTimeout(resolve, 5));
  }

  throw new Error('No action window screen rendered.');
};

describe('ActionWindow.vue', () => {
  beforeAll(async () => {
    await Promise.all([
      import('@/Components/ActionWindow/screens/PersonaScreen.vue'),
      import('@/Components/ActionWindow/screens/PersonaActionsScreen.vue'),
      // The meeting.create flow starts here when no institution was seeded.
      import('@/Components/ActionWindow/screens/InstitutionPickerScreen.vue'),
      import('@/Components/ActionWindow/screens/MeetingTypeScreen.vue'),
    ]);
  });

  beforeEach(() => {
    vi.mocked(usePage).mockReset();
  });

  it('renders nothing until it is opened', () => {
    const { wrapper } = mountWindow();

    expect(wrapper.text()).toBe('');
  });

  it('mounts the persona screen without throwing', async () => {
    const { wrapper, window } = mountWindow();
    window.open();
    await settle(wrapper);

    expect(wrapper.findAll('[data-slot="action-choice-button"]')).toHaveLength(3);
    expect(wrapper.text()).toContain('action_window.personas.representative.title');
  });

  it('choosing a persona pushes its actions, and back returns', async () => {
    const { wrapper, window } = mountWindow();
    window.open();
    await settle(wrapper);

    await wrapper.findAll('[data-slot="action-choice-button"]')[0]!.trigger('click');
    await settle(wrapper);

    expect(window.current.value.id).toBe('persona.actions');
    expect(wrapper.text()).toContain('action_window.actions.new_meeting.title');

    await wrapper.find('button[aria-label="action_window.common.back"]').trigger('click');
    await settle(wrapper);

    expect(window.current.value.id).toBe('persona');
  });

  it('skips the persona menu when the user qualifies for only one', async () => {
    const { wrapper, window } = mountWindow({
      create: { meeting: true, problem: false, reservation: false, duty: false },
      manageSettings: false,
    });
    window.open();
    await settle(wrapper);

    expect(window.current.value.id).toBe('persona.actions');
    // Replaced rather than pushed, so there is nothing to go back to.
    expect(wrapper.find('button[aria-label="action_window.common.back"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('action_window.actions.no_meeting.title');
  });

  it('the close button shuts the window', async () => {
    const { wrapper, window } = mountWindow();
    window.open();
    await settle(wrapper);

    await wrapper.find('button[aria-label="action_window.common.close"]').trigger('click');

    expect(window.isOpen.value).toBe(false);
  });

  describe('flow progress', () => {
    it('is absent on the persona menu, which is navigation rather than progress', async () => {
      const { wrapper, window } = mountWindow();
      window.open();
      await settle(wrapper);

      expect(wrapper.find('[data-slot="action-window-body"] header div.flex-1').exists()).toBe(false);
    });

    it('drops the institution step when the caller seeded one', async () => {
      const { wrapper, window } = mountWindow();
      window.open({ flow: 'meeting.create', institution: { id: '1', name: 'MIF SPK' } });
      await settle(wrapper);

      expect(wrapper.findAll('header span.rounded-full')).toHaveLength(4);
    });

    it('counts the institution step when the user actually picked one', async () => {
      const { wrapper, window } = mountWindow();
      window.open({ flow: 'meeting.create' });
      await settle(wrapper);

      // Reached through the picker, so it is part of this run's five steps — the
      // dots must not renumber just because the stack started elsewhere.
      expect(wrapper.findAll('header span.rounded-full')).toHaveLength(5);

      window.goTo('meeting.type');
      await settle(wrapper);
      expect(wrapper.findAll('header span.rounded-full')).toHaveLength(5);
    });

    /**
     * Started from an announcement there is no date to ask for, so counting the step
     * would promise a screen the run never shows.
     */
    it('drops the date step when an announcement already fixed it', async () => {
      const { wrapper, window } = mountWindow();
      window.open({
        flow: 'meeting.create',
        institution: { id: '1', name: 'MIF SPK' },
        calendarEvent: { id: 42, title: 'MIF SPK posėdis', date: '2026-09-15T18:00:00.000Z' },
      });
      await settle(wrapper);

      // Type, agenda, review — the institution came from the caller, the date from the event.
      expect(wrapper.findAll('header span.rounded-full')).toHaveLength(3);
    });
  });
});
