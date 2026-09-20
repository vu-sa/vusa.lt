import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AssignDutyUserSheet from '@/Features/Admin/Occupancy/AssignDutyUserSheet.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual<any>('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => ({
      props: {
        app: { locale: 'lt', url: 'https://vusa.test' },
        auth: { user: { isSuperAdmin: true } },
        flash: {},
      },
    }),
  };
});

describe('AssignDutyUserSheet.vue', () => {
  const stubs = {
    ...commonStubs,
    Sheet: { template: '<div><slot /></div>' },
    SheetContent: { template: '<div><slot /></div>' },
    SheetHeader: { template: '<div><slot /></div>' },
    SheetTitle: { template: '<h2><slot /></h2>' },
    SheetDescription: { template: '<p><slot /></p>' },
    DatePicker: {
      props: ['modelValue'],
      template: '<input type="date" :value="modelValue" />',
    },
    SingleSelect: { template: '<div />' },
    UserAvatar: { template: '<div class="avatar" />' },
    InflectedDutyName: { props: ['name'], template: '<span>{{ typeof name === "string" ? name : name?.lt }}</span>' },
  };

  it('renders in create mode with duty context when dutyId is provided', () => {
    const wrapper = mount(AssignDutyUserSheet, {
      props: {
        open: true,
        dutyId: 'duty-1',
        duty: { id: 'duty-1', name: { lt: 'Pirmininkas', en: 'Chair' }, institution: { name: 'VU SA MIF' } },
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Priskirti pareigoms');
    expect(wrapper.text()).toContain('Pirmininkas');
    expect(wrapper.text()).toContain('VU SA MIF');
    expect(wrapper.text()).toContain('Pradžios data');
    expect(wrapper.text()).toContain('Pabaigos data');
  });

  it('renders in edit mode with user and occupancy details when dutiable is provided', () => {
    const wrapper = mount(AssignDutyUserSheet, {
      props: {
        open: true,
        dutiable: {
          id: 'dutiable-1',
          duty_id: 'duty-1',
          dutiable_id: 'user-1',
          start_date: '2026-01-01',
          end_date: '2026-12-31',
          duty: { id: 'duty-1', name: { lt: 'Sekretorius' } },
          user: { id: 'user-1', name: 'Jonas Jonaitis', email: 'jonas@stud.vu.lt' },
        },
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Redaguoti priskyrimą');
    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.text()).toContain('Sekretorius');
    expect(wrapper.text()).toContain('Baigti kadenciją');
    expect(wrapper.text()).toContain('Ištrinti');
  });
});
