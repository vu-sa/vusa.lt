import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCGridRowOptions from '../RCGridRowOptions.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';

import { commonStubs, stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

const stubs = { ...commonStubs, Popover: stubPopover, PopoverAnchor: stubPopoverAnchor, PopoverContent: stubPopoverContent };

function mountOptions(props: Partial<InstanceType<typeof RCGridRowOptions>['$props']> = {}) {
  const hotspots = useActiveHotspot();
  const wrapper = mount(RCGridRowOptions, {
    props: {
      blockKey: 'grid-1',
      rowIndex: 0,
      canMoveUp: false,
      canMoveDown: true,
      canRemove: true,
      ...props,
    },
    global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
  });
  return { wrapper, hotspots };
}

describe('RCGridRowOptions', () => {
  it('shows the 1-indexed row label on the trigger', () => {
    const { wrapper } = mountOptions({ rowIndex: 2 });
    expect(wrapper.text()).toContain('rich-content.grid_row 3');
  });

  it('opens the popover with id `${blockKey}:row-${row}` when clicked', async () => {
    const { wrapper, hotspots } = mountOptions({ rowIndex: 1 });
    await wrapper.find('[data-rc-grid-row-options]').trigger('click');
    expect(hotspots.isPopoverOpen('grid-1:row-1')).toBe(true);
  });

  it('disables move buttons per canMoveUp/canMoveDown and emits on click', async () => {
    const { wrapper, hotspots } = mountOptions({ canMoveUp: false, canMoveDown: true });
    hotspots.openPopover('grid-1:row-0');
    await wrapper.vm.$nextTick();

    const moveUp = wrapper.findAll('button').find(b => b.text().includes('rich-content.move_up'))!;
    const moveDown = wrapper.findAll('button').find(b => b.text().includes('rich-content.move_down'))!;
    expect(moveUp.attributes('disabled')).toBeDefined();
    expect(moveDown.attributes('disabled')).toBeUndefined();

    await moveDown.trigger('click');
    expect(wrapper.emitted('move-down')).toBeTruthy();
  });

  it('hides move controls entirely when neither direction is available', async () => {
    const { wrapper, hotspots } = mountOptions({ canMoveUp: false, canMoveDown: false });
    hotspots.openPopover('grid-1:row-0');
    await wrapper.vm.$nextTick();
    expect(wrapper.text()).not.toContain('rich-content.move_up');
  });

  it('disables remove when canRemove is false, and emits remove when true', async () => {
    const { wrapper, hotspots } = mountOptions({ canRemove: false });
    hotspots.openPopover('grid-1:row-0');
    await wrapper.vm.$nextTick();
    const removeButton = wrapper.findAll('button').find(b => b.text().includes('rich-content.grid_remove_row'))!;
    expect(removeButton.attributes('disabled')).toBeDefined();

    await wrapper.setProps({ canRemove: true });
    await removeButton.trigger('click');
    expect(wrapper.emitted('remove')).toBeTruthy();
  });
});
