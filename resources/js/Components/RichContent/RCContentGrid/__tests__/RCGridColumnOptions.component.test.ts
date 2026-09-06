import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCGridColumnOptions from '../RCGridColumnOptions.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';

import { commonStubs, stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  Popover: stubPopover,
  PopoverAnchor: stubPopoverAnchor,
  PopoverContent: stubPopoverContent,
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

function mountOptions(props: Partial<InstanceType<typeof RCGridColumnOptions>['$props']> = {}) {
  const hotspots = useActiveHotspot();
  const wrapper = mount(RCGridColumnOptions, {
    props: {
      blockKey: 'grid-1',
      rowIndex: 0,
      colIndex: 0,
      type: 'tiptap',
      width: 'col-span-6',
      canMoveLeft: false,
      canMoveRight: true,
      canRemove: true,
      ...props,
    },
    global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
  });
  return { wrapper, hotspots };
}

describe('RCGridColumnOptions', () => {
  it('opens the popover with id `${blockKey}:col-${row}-${col}` when clicked', async () => {
    const { wrapper, hotspots } = mountOptions({ rowIndex: 1, colIndex: 2 });
    await wrapper.find('[data-rc-grid-column-options]').trigger('click');
    expect(hotspots.isPopoverOpen('grid-1:col-1-2')).toBe(true);
  });

  it('emits update:type when the content type select changes', async () => {
    const { wrapper, hotspots } = mountOptions();
    hotspots.openPopover('grid-1:col-0-0');
    await wrapper.vm.$nextTick();

    await wrapper.get('select').setValue('image');
    expect(wrapper.emitted('update:type')).toEqual([['image']]);
  });

  it('emits update:width when the width select changes', async () => {
    const { wrapper, hotspots } = mountOptions();
    hotspots.openPopover('grid-1:col-0-0');
    await wrapper.vm.$nextTick();

    const selects = wrapper.findAll('select');
    await selects[1]!.setValue('col-span-12');
    expect(wrapper.emitted('update:width')).toEqual([['col-span-12']]);
  });

  it('disables move buttons according to canMoveLeft/canMoveRight and emits on click', async () => {
    const { wrapper, hotspots } = mountOptions({ canMoveLeft: false, canMoveRight: true });
    hotspots.openPopover('grid-1:col-0-0');
    await wrapper.vm.$nextTick();

    const buttons = wrapper.findAll('button').filter(b => b.text().includes('rich-content.move_left') || b.text().includes('rich-content.move_right'));
    const moveLeft = buttons.find(b => b.text().includes('rich-content.move_left'))!;
    const moveRight = buttons.find(b => b.text().includes('rich-content.move_right'))!;
    expect(moveLeft.attributes('disabled')).toBeDefined();
    expect(moveRight.attributes('disabled')).toBeUndefined();

    await moveRight.trigger('click');
    expect(wrapper.emitted('move-right')).toBeTruthy();
  });

  it('hides move controls entirely when neither direction is available', async () => {
    const { wrapper, hotspots } = mountOptions({ canMoveLeft: false, canMoveRight: false });
    hotspots.openPopover('grid-1:col-0-0');
    await wrapper.vm.$nextTick();
    expect(wrapper.text()).not.toContain('rich-content.move_left');
  });

  it('disables remove when canRemove is false', async () => {
    const { wrapper, hotspots } = mountOptions({ canRemove: false });
    hotspots.openPopover('grid-1:col-0-0');
    await wrapper.vm.$nextTick();

    const removeButtons = wrapper.findAll('button').filter(b => b.text().includes('rich-content.grid_remove_column'));
    expect(removeButtons[0]!.attributes('disabled')).toBeDefined();
  });

  it('emits remove when canRemove is true and the button is clicked', async () => {
    const { wrapper, hotspots } = mountOptions({ canRemove: true });
    hotspots.openPopover('grid-1:col-0-0');
    await wrapper.vm.$nextTick();

    const removeButtons = wrapper.findAll('button').filter(b => b.text().includes('rich-content.grid_remove_column'));
    await removeButtons[0]!.trigger('click');
    expect(wrapper.emitted('remove')).toBeTruthy();
  });
});
