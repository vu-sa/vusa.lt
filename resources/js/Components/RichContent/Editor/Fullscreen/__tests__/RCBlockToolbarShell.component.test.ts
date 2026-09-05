import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import RCBlockToolbarShell from '../RCBlockToolbarShell.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../useActiveHotspot';
import type { ContentPart } from '../../../Types';
import { commonStubs, stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const stubs = {
  ...commonStubs,
  Popover: stubPopover,
  PopoverAnchor: stubPopoverAnchor,
  PopoverContent: stubPopoverContent,
};

const content: ContentPart = { type: 'tiptap', json_content: {}, key: 'k1' };

function mountShell(overrides: Record<string, unknown> = {}, slots: Record<string, string> = {}) {
  const hotspots = useActiveHotspot();
  const wrapper = mount(RCBlockToolbarShell, {
    props: {
      content,
      blockKey: 'k1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
      ...overrides,
    },
    slots,
    global: {
      stubs,
      provide: { [ACTIVE_HOTSPOT_KEY]: hotspots },
    },
  });
  return { wrapper, hotspots };
}

describe('RCBlockToolbarShell', () => {
  it('opens its popover (claims the toolbar hotspot) when the trigger button is clicked', async () => {
    const { wrapper, hotspots } = mountShell();

    await wrapper.find('button').trigger('click');

    expect(hotspots.isPopoverOpen('k1:toolbar')).toBe(true);
  });

  it('keeps the settings trigger visible without changing the block layout', () => {
    const { wrapper } = mountShell();

    expect(wrapper.classes()).toContain('absolute');
    expect(wrapper.classes()).toContain('top-6');
    expect(wrapper.classes()).toContain('opacity-100');
  });

  it('move-up / move-down / open-form / delete buttons emit the matching event', async () => {
    const { wrapper, hotspots } = mountShell();
    hotspots.openPopover('k1:toolbar');
    await wrapper.vm.$nextTick();

    const buttons = wrapper.findAll('button');
    // buttons[0] is the trigger; the rest are inside the popover content.
    await buttons[1]!.trigger('click'); // move up
    expect(wrapper.emitted('move-up')).toHaveLength(1);
    await buttons[2]!.trigger('click'); // move down
    expect(wrapper.emitted('move-down')).toHaveLength(1);
    await buttons[3]!.trigger('click'); // open form
    expect(wrapper.emitted('open-form')).toHaveLength(1);
    await buttons[4]!.trigger('click'); // delete
    expect(wrapper.emitted('delete')).toHaveLength(1);
  });

  it('disables move-up/move-down/delete per the corresponding can* prop', async () => {
    const { wrapper, hotspots } = mountShell({ canMoveUp: false, canMoveDown: false, canDelete: false });
    hotspots.openPopover('k1:toolbar');
    await wrapper.vm.$nextTick();

    const buttons = wrapper.findAll('button');
    expect(buttons[1]!.attributes('disabled')).toBeDefined();
    expect(buttons[2]!.attributes('disabled')).toBeDefined();
    expect(buttons[4]!.attributes('disabled')).toBeDefined();
  });

  it('renders the default slot inside the popover for type-specific controls', async () => {
    const { wrapper, hotspots } = mountShell({}, { default: '<div class="type-specific">Width picker</div>' });
    hotspots.openPopover('k1:toolbar');
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.type-specific').exists()).toBe(true);
  });

  it('does not render popover content until the toolbar hotspot is open', () => {
    const { wrapper } = mountShell({}, { default: '<div class="type-specific">Width picker</div>' });
    expect(wrapper.find('.type-specific').exists()).toBe(false);
  });
});
