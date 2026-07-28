import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import { DynamicListInput } from '../index';

function mountList(props: Record<string, unknown> = {}) {
  return mount(DynamicListInput, {
    props: {
      modelValue: ['a', 'b'],
      createItem: () => '',
      'onUpdate:modelValue': () => {},
      ...props,
    },
    slots: {
      // Render the item's own value so a stale key/item mismatch would show up as
      // wrong text, not just a wrong count.
      item: '<template #item="{ item }"><span class="item-slot">{{ item }}</span></template>',
    },
  });
}

describe('DynamicListInput', () => {
  it('renders the full header row (drag handle + #N + delete) by default', () => {
    const wrapper = mountList();
    expect(wrapper.text()).toContain('#1');
    expect(wrapper.text()).toContain('#2');
  });

  it('drops the header row in compact mode', () => {
    const wrapper = mountList({ compact: true });
    expect(wrapper.text()).not.toContain('#1');
  });

  it('renders one item-slot per model entry, in order', () => {
    const wrapper = mountList();
    expect(wrapper.findAll('.item-slot').map(s => s.text())).toEqual(['a', 'b']);
  });

  it('shows the empty state and add-first button when the list is empty', () => {
    const wrapper = mountList({ modelValue: [] });
    expect(wrapper.text()).toContain('No items added yet');
    expect(wrapper.findAll('.item-slot')).toHaveLength(0);
  });

  it('falls back to the empty state when modelValue is undefined (optional field missing)', () => {
    // Editors bind DynamicListInput to optional fields (e.g. carousel slide
    // `decorations`) that are absent on seeded/legacy rows. A missing prop must
    // not crash the template on `items.length`.
    const wrapper = mount(DynamicListInput, {
      props: {
        createItem: () => 'new',
        'onUpdate:modelValue': () => {},
      },
      slots: {
        item: '<template #item="{ item }"><span class="item-slot">{{ item }}</span></template>',
      },
    });
    expect(wrapper.text()).toContain('No items added yet');
    expect(wrapper.findAll('.item-slot')).toHaveLength(0);
  });

  it('resyncs keys and renders correctly after a wholesale replacement of the items array (form load / undo-redo)', async () => {
    const wrapper = mountList({ modelValue: ['a', 'b'] });
    expect(wrapper.findAll('.item-slot').map(s => s.text())).toEqual(['a', 'b']);

    // Simulate the parent reassigning the whole array from outside (not via addItem/removeItem) —
    // itemKeys used to only grow on this path, never shrink or resync, and stayed stale.
    await wrapper.setProps({ modelValue: ['x', 'y', 'z', 'w'] });
    expect(wrapper.findAll('.item-slot').map(s => s.text())).toEqual(['x', 'y', 'z', 'w']);

    await wrapper.setProps({ modelValue: ['only-one'] });
    expect(wrapper.findAll('.item-slot').map(s => s.text())).toEqual(['only-one']);

    // Growing again after a shrink must not reuse a stale/duplicate key either.
    await wrapper.setProps({ modelValue: ['p', 'q', 'r'] });
    expect(wrapper.findAll('.item-slot').map(s => s.text())).toEqual(['p', 'q', 'r']);
  });

  it('add item appends via createItem and emits the updated array', async () => {
    const modelValue = ['a', 'b'];
    const wrapper = mount(DynamicListInput, {
      props: {
        modelValue,
        createItem: () => 'new',
        'onUpdate:modelValue': (val: string[]) => wrapper.setProps({ modelValue: val }),
      },
      slots: { item: '<span class="item-slot" />' },
    });

    const addButton = wrapper.findAll('button').find(b => b.text().includes('Add item'))!;
    await addButton.trigger('click');
    expect(wrapper.props('modelValue')).toEqual(['a', 'b', 'new']);
  });

  it('remove item via the delete button removes just that item', async () => {
    const wrapper = mount(DynamicListInput, {
      props: {
        modelValue: ['a', 'b', 'c'],
        createItem: () => 'new',
        'onUpdate:modelValue': (val: string[]) => wrapper.setProps({ modelValue: val }),
      },
      slots: { item: '<template #item="{ item }"><span class="item-slot">{{ item }}</span></template>' },
    });

    // Delete buttons are the only <button> elements besides "Add item"; the second
    // item's delete button is the 2nd button overall (1 per item, in order).
    const deleteButtons = wrapper.findAll('button').filter(b => !b.text().includes('Add item'));
    await deleteButtons[1]!.trigger('click');

    expect(wrapper.props('modelValue')).toEqual(['a', 'c']);
  });
});
