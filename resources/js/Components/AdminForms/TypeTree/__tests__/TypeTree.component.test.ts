import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import TypeTreeChecklist from '../TypeTreeChecklist.vue';
import TypeTreeMultiSelect from '../TypeTreeMultiSelect.vue';
import TypeTreeSelect from '../TypeTreeSelect.vue';

const types = [
  { id: 6, title: 'Koordinatorius', parent_id: null },
  { id: 9, title: 'Administratorius', parent_id: 6 },
  { id: 4, title: 'Pirmininkas', parent_id: 9 },
];

const manyTypes = Array.from({ length: 20 }, (_, index) => ({ id: index + 1, title: `Tipas ${String(index + 1).padStart(2, '0')}`, parent_id: index === 0 ? null : 1 }));

describe('TypeTreeSelect', () => {
  it('indents a native option per level and leaves out an excluded type with its subtypes', () => {
    const wrapper = mount(TypeTreeSelect, { props: { types, exclude: [9], placeholder: 'Nėra', modelValue: null } });

    const labels = wrapper.findAll('option').map(option => option.text());
    expect(labels).toEqual(['Nėra', 'Koordinatorius']);
  });

  it('emits the picked id in the id type the form keeps', async () => {
    const wrapper = mount(TypeTreeSelect, { props: { types, asString: true, modelValue: null } });

    await wrapper.find('select').setValue('9');

    expect(wrapper.emitted('update:modelValue')!.at(-1)).toEqual(['9']);
    expect(wrapper.findAll('option')[1].element.textContent).toBe('    Administratorius');
  });

  it('switches to a searchable picker labelled with the parent path past 16 types', () => {
    const wrapper = mount(TypeTreeSelect, { props: { types: manyTypes, modelValue: 2 } });
    const picker = wrapper.findComponent({ name: 'SingleSelect' });

    expect(picker.exists()).toBe(true);
    expect(picker.props('modelValue')).toMatchObject({ path: 'Tipas 01 › Tipas 02' });
  });
});

describe('TypeTreeMultiSelect', () => {
  it('offers the types in tree order with their path and maps the selection to ids', async () => {
    const wrapper = mount(TypeTreeMultiSelect, { props: { types, modelValue: [6], omit: [9] } });
    const picker = wrapper.findComponent({ name: 'MultiSelect' });

    expect(picker.props('options').map((node: { path: string }) => node.path))
      .toEqual(['Koordinatorius', 'Koordinatorius › Administratorius › Pirmininkas']);

    picker.vm.$emit('update:modelValue', picker.props('options'));
    await wrapper.vm.$nextTick();

    expect(wrapper.emitted('update:modelValue')!.at(-1)).toEqual([[6, 4]]);
  });
});

describe('TypeTreeChecklist', () => {
  it('indents each level and toggles ids', async () => {
    const wrapper = mount(TypeTreeChecklist, { props: { types, modelValue: [9] } });

    expect(wrapper.get('[data-testid="type-tree-check-4"]').attributes('style')).toContain('padding-left: 3rem');

    wrapper.findAllComponents({ name: 'Checkbox' })[0].vm.$emit('update:modelValue', true);

    expect(wrapper.emitted('update:modelValue')!.at(-1)).toEqual([[9, 6]]);
  });
});
