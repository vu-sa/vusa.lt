import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import BlockPickerDialog from '../BlockPickerDialog.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@/Composables/useIsMobile', () => ({ useIsMobile: () => ({ value: true }) }));

describe('BlockPickerDialog on a phone', () => {
  it('previews a block before adding it and can return to the list', async () => {
    const wrapper = mount(BlockPickerDialog, {
      props: { open: true },
      global: { stubs: { ...commonStubs, BlockPreviewRenderer: true } },
    });
    await flushPromises();

    await wrapper.findAll('button').find(button => button.text().includes('Kortelė'))!.trigger('click');

    expect(wrapper.emitted('select')).toBeUndefined();
    expect(wrapper.find('[aria-label="rich-content.back_to_blocks"]').exists()).toBe(true);
    expect(wrapper.find('.rc-canvas').attributes('style')).toContain('scale(0.25)');

    await wrapper.find('[aria-label="rich-content.back_to_blocks"]').trigger('click');
    expect(wrapper.find('input[type="search"]').exists()).toBe(true);

    await wrapper.findAll('button').find(button => button.text().includes('Kortelė'))!.trigger('click');
    const add = wrapper.findAll('button').find(button => button.text().includes('rich-content.add_this_block'))!;
    await add.trigger('click');

    expect(wrapper.emitted('select')).toEqual([['shadcn-card']]);
    expect(wrapper.emitted('update:open')).toEqual([[false]]);
  });
});
