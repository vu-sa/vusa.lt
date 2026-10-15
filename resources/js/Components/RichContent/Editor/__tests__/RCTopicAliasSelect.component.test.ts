import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import RCTopicAliasSelect from '../RCTopicAliasSelect.vue';

describe('RCTopicAliasSelect', () => {
  it('lists only is_topic tags from the shared page prop, plus a "no topic" option', () => {
    const wrapper = mount(RCTopicAliasSelect, { props: { modelValue: undefined } });

    const values = wrapper.findAll('option').map(o => o.element.value);
    expect(values).toEqual(['', 'akademine-informacija']);
  });

  it('selecting a topic emits its alias', async () => {
    const wrapper = mount(RCTopicAliasSelect, { props: { modelValue: undefined } });

    await wrapper.find('select').setValue('akademine-informacija');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['akademine-informacija']);
  });

  it('selecting "no topic" emits undefined', async () => {
    const wrapper = mount(RCTopicAliasSelect, { props: { modelValue: 'akademine-informacija' } });

    await wrapper.find('select').setValue('');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([undefined]);
  });
});
