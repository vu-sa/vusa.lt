import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import RCCategoryAliasSelect from '../RCCategoryAliasSelect.vue';

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

describe('RCCategoryAliasSelect', () => {
  it('lists every category from the shared page prop, plus a "no category" option', () => {
    const wrapper = mount(RCCategoryAliasSelect, { props: { modelValue: undefined }, global: { stubs } });

    const values = wrapper.findAll('option').map(o => o.attributes('value'));
    expect(values).toEqual(['__none__', 'freshmen-camps', 'announcements']);
  });

  it('selecting a category emits its alias', async () => {
    const wrapper = mount(RCCategoryAliasSelect, { props: { modelValue: undefined }, global: { stubs } });

    await wrapper.find('select').setValue('announcements');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['announcements']);
  });

  it('selecting "no category" emits undefined, not the sentinel', async () => {
    const wrapper = mount(RCCategoryAliasSelect, { props: { modelValue: 'announcements' }, global: { stubs } });

    await wrapper.find('select').setValue('__none__');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([undefined]);
  });
});
