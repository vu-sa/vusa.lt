import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

import InstitutionReportScreen from '@/Components/ActionWindow/screens/InstitutionReportScreen.vue';
import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mountScreen = () => {
  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      window.open({ flow: 'institution.report', institution: { id: '1', name: 'VU SA MIF' } });
      return () => h(InstitutionReportScreen);
    },
  }), { global: { stubs: { ...commonStubs } } });

  return { wrapper, window };
};

const choices = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('[data-slot="action-choice-button"]');

describe('InstitutionReportScreen.vue', () => {
  it('opens on the report choice for the named institution', () => {
    const { wrapper, window } = mountScreen();

    expect(window.current.value.id).toBe('institution.report');
    expect(wrapper.text()).toContain('VU SA MIF');
  });

  it('records a meeting without asking for the institution again', async () => {
    const { wrapper, window } = mountScreen();

    await choices(wrapper)[0]!.trigger('click');

    expect(window.current.value.id).toBe('meeting.type');
  });

  it('marks a period without meetings', async () => {
    const { wrapper, window } = mountScreen();

    await choices(wrapper)[1]!.trigger('click');

    expect(window.current.value.id).toBe('checkin.until');
  });
});
