import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import UserMergeDutyEmails from '../UserMergeDutyEmails.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const options = [
  { id: 'p1', source_user_id: 'source', duty: 'Pirmininkas', institution: 'Taryba', email: 'old@vusa.lt', start_date: '2024-01-01', end_date: null, overlap_group: 'g1' },
  { id: 'p2', source_user_id: 'other', duty: 'Pirmininkas', institution: 'Taryba', email: 'other@vusa.lt', start_date: '2024-06-01', end_date: null, overlap_group: 'g1' },
  { id: 'p3', source_user_id: 'source', duty: 'Atstovas', institution: 'Senatas', email: 'old@vusa.lt', start_date: '2020-01-01', end_date: '2021-01-01', overlap_group: 'g2' },
];
const fetchMock = vi.fn();
const originalFetch = globalThis.fetch;
const Checkbox = {
  props: ['modelValue', 'id'],
  emits: ['update:modelValue'],
  template: '<input :id="id" type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
};
const render = () => mount(UserMergeDutyEmails, {
  props: { records: [{ id: 'kept' }, { id: 'source' }, { id: 'other' }], targetId: 'kept', loginEmail: 'login@stud.vu.lt' },
  global: { stubs: { ...commonStubs, Checkbox } },
});

describe('UserMergeDutyEmails', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock);
    fetchMock.mockReset().mockImplementation(async () => {
      const response = { success: true, data: options };
      return {
        ok: true, status: 200, headers: new Headers({ 'content-type': 'application/json' }),
        json: async () => response, text: async () => JSON.stringify(response), clone() { return this; },
      };
    });
  });
  afterEach(() => vi.stubGlobal('fetch', originalFetch));

  it('loads periods with no preselected checkboxes and posts only the account ids and login', async () => {
    const wrapper = render();
    await flushPromises();

    expect(wrapper.findAll('input[type=checkbox]')).toHaveLength(3);
    expect(wrapper.findAll('input[type=checkbox]').every(input => !(input.element as HTMLInputElement).checked)).toBe(true);
    expect(JSON.parse(fetchMock.mock.calls.at(-1)![1].body)).toEqual({
      kept_user_id: 'kept', source_user_ids: ['source', 'other'], login_email: 'login@stud.vu.lt',
    });
  });

  it('allows independent periods but keeps one email choice per overlapping group', async () => {
    const wrapper = render();
    await flushPromises();
    await wrapper.find('#merge-email-p1').setValue(true);
    await wrapper.find('#merge-email-p3').setValue(true);
    await wrapper.find('#merge-email-p2').setValue(true);

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([['p3', 'p2']]);
  });

  it('clears selected periods when the kept account or login changes', async () => {
    const wrapper = render();
    await flushPromises();
    await wrapper.find('#merge-email-p1').setValue(true);
    await wrapper.setProps({ targetId: 'source', loginEmail: 'old@vusa.lt' });
    await flushPromises();

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([[]]);
    expect(JSON.parse(fetchMock.mock.calls.at(-1)![1].body).source_user_ids).toEqual(['kept', 'other']);
  });

  it('reports a preview failure and permits retrying', async () => {
    fetchMock.mockRejectedValueOnce(new Error('offline'));
    const wrapper = render();
    await flushPromises();

    expect(wrapper.find('[role=alert]').exists()).toBe(true);
    await wrapper.find('button').trigger('click');
    await flushPromises();
    expect(wrapper.findAll('input[type=checkbox]')).toHaveLength(3);
  });
});
