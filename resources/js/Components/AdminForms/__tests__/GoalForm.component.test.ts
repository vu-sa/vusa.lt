import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import GoalForm from '../GoalForm.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('GoalForm pickers', () => {
  it('keeps cadence ids as strings, clears to null and drops the duty when the numeric tenant changes', async () => {
    const wrapper = mount(GoalForm, {
      props: {
        goal: { tenant_id: 1, responsible_duty_id: 'duty-1', cadence_id: 'term-1' },
        tenants: [{ id: 1, shortname: 'MIF' }, { id: 2, shortname: 'FF' }],
        cadences: [{ id: 'term-1', label: '2026–2027' }],
        statuses: [],
        duties: [],
      },
      global: {
        stubs: {
          ...commonStubs,
          FormPage: { template: '<form @submit.prevent><slot /><slot name="aside" /></form>' },
          TiptapEditor: true,
          SingleSelect: true,
        },
      },
    });
    const { form } = wrapper.vm as unknown as {
      form: { cadence_id: string | null; tenant_id: number | null; responsible_duty_id: string | null };
    };

    await wrapper.get('select#goal-cadence').setValue('');
    expect(form.cadence_id).toBeNull();
    await wrapper.get('select#goal-cadence').setValue('term-1');
    expect(form.cadence_id).toBe('term-1');
    await wrapper.get('select#goal-tenant').setValue('2');
    expect(form.tenant_id).toBe(2);
    expect(form.responsible_duty_id).toBeNull();
    wrapper.unmount();
  });
});
