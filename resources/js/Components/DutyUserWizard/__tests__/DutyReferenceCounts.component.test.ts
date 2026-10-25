import { mount } from '@vue/test-utils';
import { reactive, ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';

import Step2DutySelect from '../Step2DutySelect.vue';
import Step4Review from '../Step4Review.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@/Composables/useDuplicateDutyCheck', () => ({
  useDuplicateDutyCheck: () => ({ matches: ref({ same_institution: [], same_tenant: [], other_institution: [], other_institution_count: 0 }) }),
}));

const makeDuty = (id: string, name: string, count: number, places: number | null) => ({
  id, name, places_to_occupy: places,
  current_users: Array.from({ length: count }, (_, i) => ({ id: `${id}-${i}`, name: `Member ${i}` })),
});

const stubs = {
  ...commonStubs,
  ScrollArea: { template: '<div><slot /></div>' },
  InflectedDutyName: { props: ['name'], template: '<span>{{ name }}</span>' },
};

describe('duty wizard reference counts', () => {
  it('orders empty duties first and alphabetically without capacity-based statuses', async () => {
    const duties = [
      makeDuty('partial', 'Beta', 1, 38),
      makeDuty('full', 'Alpha', 1, 1),
      makeDuty('over', 'Delta', 2, 1),
      makeDuty('empty', 'Zeta', 0, null),
    ];
    const wizard = {
      state: reactive({ institution: { id: 'institution', name: 'Institution', duties }, loading: { duties: false } }),
      setDuty: vi.fn(), nextStep: vi.fn(), loadDutyTypes: vi.fn(),
    };
    const wrapper = mount(Step2DutySelect, { global: { stubs, provide: { dutyUserWizard: wizard } } });
    const rows = wrapper.findAll('button').filter(button => duties.some(duty => button.text().includes(duty.name)));

    expect(rows.map(row => duties.find(duty => row.text().includes(duty.name))?.id)).toEqual(['empty', 'full', 'partial', 'over']);
    expect(rows[0].text()).toContain('Neužimta');
    for (const label of ['Dalinai užimta', 'Pilnai užimta', 'Viršija limitą', '/ 38', '/ 1']) {
      expect(wrapper.text()).not.toContain(label);
    }

    await rows[3].trigger('click');
    expect(wizard.setDuty).toHaveBeenCalledWith(duties[2]);
    expect(wizard.nextStep).toHaveBeenCalledOnce();
  });

  it.each([null, 1, 38])('reviews member changes independently of a reference of %s', (places) => {
    const wrapper = mount(Step4Review, {
      global: {
        stubs,
        provide: {
          dutyUserWizard: {
            state: { institution: { name: 'Institution' }, duty: makeDuty('duty', 'Duty', 1, places), userChanges: [], newUsersToCreate: [] },
            projectedUserCount: ref(2),
          },
        },
      },
    });

    expect(wrapper.text()).toContain('Nariai');
    expect(wrapper.text()).not.toContain('Vietų skaičiaus neatitikimas');
    expect(wrapper.find('input[type="number"]').exists()).toBe(false);
  });
});
