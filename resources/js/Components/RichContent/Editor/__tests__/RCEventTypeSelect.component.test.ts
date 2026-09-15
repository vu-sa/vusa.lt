import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import RCEventTypeSelect from '../RCEventTypeSelect.vue';

const stubs = {
  Select: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
  },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
  SelectContent: { template: '<slot />' },
  SelectItem: { props: ['value'], template: '<option :value="value"><slot /></option>' },
};

describe('RCEventTypeSelect', () => {
  it('lists every event type from the shared page prop, plus a "no event type" option', () => {
    const wrapper = mount(RCEventTypeSelect, { props: { modelValue: undefined }, global: { stubs } });

    const values = wrapper.findAll('option').map(o => o.attributes('value'));
    expect(values).toEqual(['__none__', 'stovykla', 'konferencija']);
  });

  it('selecting an event type emits its slug', async () => {
    const wrapper = mount(RCEventTypeSelect, { props: { modelValue: undefined }, global: { stubs } });

    await wrapper.find('select').setValue('konferencija');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['konferencija']);
  });

  it('selecting "no event type" emits undefined, not the sentinel', async () => {
    const wrapper = mount(RCEventTypeSelect, { props: { modelValue: 'konferencija' }, global: { stubs } });

    await wrapper.find('select').setValue('__none__');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([undefined]);
  });
});
