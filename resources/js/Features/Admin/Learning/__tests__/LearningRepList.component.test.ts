import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { LearningRepresentative } from '@/Types/learning';
import { createMockPage } from '@/tests/helpers/createMockPage';
import LearningRepList from '../LearningRepList.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => vi.mocked(usePage).mockReturnValue(createMockPage()));

function representative(id: string, done: number, total: number, incoming = false): LearningRepresentative {
  return {
    id, name: id, incoming, lastActivity: null,
    institutions: [{ id: 'spk', title: 'SPK' }],
    totals: { essentialDone: done, essentialTotal: total, allDone: done, allTotal: total },
  };
}

describe('LearningRepList', () => {
  it('puts incoming representatives first then sorts by essential proportion rather than raw count', () => {
    const wrapper = mount(LearningRepList, { props: { reps: [
      representative('Finished', 2, 2), representative('Half', 1, 2),
      representative('Quarter', 2, 8), representative('Incoming', 1, 1, true),
    ] } });
    expect(wrapper.findAll('[data-rep]').map(row => row.attributes('data-rep'))).toEqual(['Incoming', 'Quarter', 'Half', 'Finished']);
    expect(wrapper.get('[data-rep="Quarter"] a').attributes('href')).toContain('learningPath.rep');
    expect(wrapper.get('[data-rep="Quarter"] [role="progressbar"]').attributes('aria-valuenow')).toBe('2');
    expect(wrapper.text()).toContain('learning_people.incoming');
    wrapper.unmount();
  });

  it('combines case insensitive name or institution search with the institution filter and clears both', async () => {
    const anna = representative('Anna', 0, 2);
    const ben = representative('Ben', 1, 2);
    ben.institutions = [{ id: 'senate', title: 'Senate' }];
    const wrapper = mount(LearningRepList, { props: { reps: [anna, ben] } });
    await wrapper.get('input[type="search"]').setValue('ANNA');
    expect(wrapper.findAll('[data-rep]').map(row => row.attributes('data-rep'))).toEqual(['Anna']);
    await wrapper.get('input[type="search"]').setValue('SENATE');
    expect(wrapper.findAll('[data-rep]').map(row => row.attributes('data-rep'))).toEqual(['Ben']);
    await wrapper.get('select').setValue('spk');
    expect(wrapper.findAll('[data-rep]')).toHaveLength(0);
    await wrapper.get('[data-slot="empty-state"] button').trigger('click');
    expect(wrapper.findAll('[data-rep]')).toHaveLength(2);
    await wrapper.get('select').setValue('senate');
    expect(wrapper.findAll('[data-rep]').map(row => row.attributes('data-rep'))).toEqual(['Ben']);
    wrapper.unmount();
  });

  it('shows a teaching empty state and a defined zero progress value', () => {
    const empty = mount(LearningRepList, { props: { reps: [] } });
    expect(empty.get('[data-slot="empty-state"]').text()).toContain('learning_people.no_reps');
    empty.unmount();
    const wrapper = mount(LearningRepList, { props: { reps: [representative('No essentials', 0, 0)] } });
    expect(wrapper.get('[role="progressbar"]').attributes('aria-valuenow')).toBe('0');
    expect(wrapper.get('[role="progressbar"] > div').attributes('style')).toContain('width: 0%');
    wrapper.unmount();
  });
});
