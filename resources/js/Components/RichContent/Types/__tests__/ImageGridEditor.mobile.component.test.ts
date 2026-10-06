import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import ImageGridEditor from '../ImageGridEditor.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@/Composables/useIsMobile', () => ({ useIsMobile: () => ({ value: true }) }));

describe('ImageGridEditor on a phone', () => {
  it('opens focal-point editing in place and returns to the image grid', async () => {
    const wrapper = mount(ImageGridEditor, {
      props: { modelValue: [{ colspan: 'col-span-2', image: '/photo.jpg', alt: 'Photo' }] },
      global: {
        stubs: {
          ...commonStubs,
          TiptapImageButton: { template: '<div><slot /></div>' },
          FocalPointPicker: { template: '<div data-testid="focal-point-picker" />' },
        },
      },
    });

    const focalPoint = wrapper.findAll('button').find(button => button.text().includes('rich-content.set_focal_point'))!;
    await focalPoint.trigger('click');

    expect(wrapper.find('[data-testid="focal-point-picker"]').exists()).toBe(true);
    await wrapper.findAll('button').find(button => button.text().includes('rich-content.back_to_images'))!.trigger('click');
    expect(wrapper.find('[aria-label="rich-content.tile_options"]').exists()).toBe(true);
  });
});
