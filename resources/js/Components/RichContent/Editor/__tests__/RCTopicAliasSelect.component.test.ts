import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import RCTopicAliasSelect from '../RCTopicAliasSelect.vue';

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

describe('RCTopicAliasSelect', () => {
  it('lists only is_topic tags from the shared page prop, plus a "no topic" option', () => {
    const wrapper = mount(RCTopicAliasSelect, { props: { modelValue: undefined }, global: { stubs } });

    const values = wrapper.findAll('option').map(o => o.attributes('value'));
    expect(values).toEqual(['__none__', 'akademine-informacija']);
  });

  it('selecting a topic emits its alias', async () => {
    const wrapper = mount(RCTopicAliasSelect, { props: { modelValue: undefined }, global: { stubs } });

    await wrapper.find('select').setValue('akademine-informacija');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['akademine-informacija']);
  });

  it('selecting "no topic" emits undefined, not the sentinel', async () => {
    const wrapper = mount(RCTopicAliasSelect, { props: { modelValue: 'akademine-informacija' }, global: { stubs } });

    await wrapper.find('select').setValue('__none__');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([undefined]);
  });
});
