import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import RCImageHotspot from '../RCImageHotspot.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../useActiveHotspot';

import { commonStubs, stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  Popover: stubPopover,
  PopoverAnchor: stubPopoverAnchor,
  PopoverContent: stubPopoverContent,
  ImageSelector: true,
};

describe('RCImageHotspot', () => {
  it('lets an author delete an empty image placeholder', async () => {
    const hotspots = useActiveHotspot();
    const wrapper = mount(RCImageHotspot, {
      props: {
        imageUrl: '',
        blockKey: 'gallery-1',
        imageIndex: 0,
      },
      global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
    });

    hotspots.openPopover('gallery-1:image-0');
    await wrapper.vm.$nextTick();

    const deleteButton = wrapper.findAll('button').find(button => button.text().includes('rich-content.delete_image'));
    await deleteButton!.trigger('click');

    expect(wrapper.emitted('delete')).toHaveLength(1);
  });

  it('disables deletion when canDelete is false', async () => {
    const hotspots = useActiveHotspot();
    const wrapper = mount(RCImageHotspot, {
      props: {
        imageUrl: '',
        blockKey: 'gallery-1',
        imageIndex: 0,
        canDelete: false,
      },
      global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
    });

    hotspots.openPopover('gallery-1:image-0');
    await wrapper.vm.$nextTick();

    const deleteButton = wrapper.findAll('button').find(button => button.text().includes('rich-content.delete_image'));
    expect(deleteButton!.attributes('disabled')).toBeDefined();
    await deleteButton!.trigger('click');

    expect(wrapper.emitted('delete')).toBeUndefined();
  });
});
