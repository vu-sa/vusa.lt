import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCImageListBlockToolbar from '../RCImageListBlockToolbar.vue';

const stubs = {
  RCBlockToolbarShell: { template: '<div><slot /></div>' },
  RCSectionOptions: { template: '<div data-test="section-options" />' },
  RCWidthPicker: {
    props: ['modelValue', 'allowedWidths'],
    template: '<button data-test="width-picker" @click="$emit(\'update:modelValue\', \'full\')">{{ modelValue }}</button>',
  },
  Select: {
    props: ['modelValue'],
    template: '<button data-test="select" @click="$emit(\'update:modelValue\', \'2\')">{{ modelValue }}</button>',
  },
  Switch: {
    props: ['modelValue'],
    template: '<button data-test="switch" @click="$emit(\'update:modelValue\', false)">{{ modelValue }}</button>',
  },
  ImageSelector: true,
};

describe('RCImageListBlockToolbar', () => {
  it('moves an image from the block options menu', async () => {
    const wrapper = mount(RCImageListBlockToolbar, {
      props: {
        content: {
          type: 'image-grid',
          json_content: [
            { colspan: 'col-span-2', image: '/first.webp', alt: 'First' },
            { colspan: 'col-span-2', image: '/second.webp', alt: 'Second' },
          ],
          options: null,
        },
        blockKey: 'image-grid-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: { stubs },
    });

    const moveDown = wrapper.findAll('button[title="rich-content.move_down"]')[0]!;
    await moveDown.trigger('click');

    const updated = wrapper.emitted('update:content')?.at(-1)?.[0] as { json_content: { image: string }[] };
    expect(updated.json_content.map(image => image.image)).toEqual(['/second.webp', '/first.webp']);
  });

  it('exposes gallery layout, section, and width settings in the block options menu', async () => {
    const wrapper = mount(RCImageListBlockToolbar, {
      props: {
        content: {
          type: 'photo-gallery',
          json_content: [],
          options: { columns: '4', gap: 'medium', showLightbox: true },
        },
        blockKey: 'gallery-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('rich-content.columns');
    expect(wrapper.text()).toContain('rich-content.gap_size');
    expect(wrapper.text()).toContain('rich-content.enable_lightbox');
    expect(wrapper.find('[data-test="section-options"]').exists()).toBe(true);

    await wrapper.find('[data-test="width-picker"]').trigger('click');

    const updated = wrapper.emitted('update:content')?.at(-1)?.[0] as { options: { width: string } };
    expect(updated.options.width).toBe('full');

    await wrapper.findAll('[data-test="select"]')[0]!.trigger('click');
    const columnsUpdate = wrapper.emitted('update:content')?.at(-1)?.[0] as { options: { columns: string } };
    expect(columnsUpdate.options.columns).toBe('2');

    await wrapper.find('[data-test="switch"]').trigger('click');

    const galleryUpdate = wrapper.emitted('update:content')?.at(-1)?.[0] as { options: { showLightbox: boolean } };
    expect(galleryUpdate.options.showLightbox).toBe(false);
  });
});
