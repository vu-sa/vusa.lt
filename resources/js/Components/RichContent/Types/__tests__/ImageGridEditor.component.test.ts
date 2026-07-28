import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import ImageGridEditor from '../ImageGridEditor.vue';
import { commonStubs } from '@/tests/stubs';

/**
 * TiptapImageButton opens a full file-picker dialog internally — irrelevant to what
 * this editor is responsible for. Stubbed to a plain button that fires the same
 * `submit:object` event the real ImageSelector emits, so this test exercises this
 * component's own add/replace/remove/reorder logic instead.
 */
const stubs = {
  ...commonStubs,
  TiptapImageButton: {
    emits: ['submit:object'],
    methods: {
      emitSample() {
        this.$emit('submit:object', { src: '/new.jpg', alt: 'New alt', title: '' });
      },
    },
    template: '<button class="image-btn-stub" @click="emitSample"><slot /></button>',
  },
};

describe('ImageGridEditor', () => {
  it('shows the empty state with an add-first-image button when there are no images', () => {
    const wrapper = mount(ImageGridEditor, { props: { modelValue: [] }, global: { stubs } });
    expect(wrapper.text()).toContain('rich-content.no_images');
    // Both the empty-state "add first image" button and the always-present trailing
    // "add image" button render when the list is empty.
    expect(wrapper.findAll('.image-btn-stub').length).toBeGreaterThanOrEqual(1);
  });

  it('adding an image preserves alt text from the picker (the previously-discarded field)', async () => {
    const modelValue: any[] = [];
    const wrapper = mount(ImageGridEditor, {
      props: { modelValue, 'onUpdate:modelValue': (val: unknown) => wrapper.setProps({ modelValue: val }) },
      global: { stubs },
    });

    await wrapper.find('.image-btn-stub').trigger('click');

    const emitted = wrapper.emitted('update:modelValue')!;
    expect(emitted[0]![0]).toEqual([
      { colspan: 'col-span-2', image: '/new.jpg', alt: 'New alt', title: '' },
    ]);
  });

  it('renders one tile per image with alt text prefilled', () => {
    const wrapper = mount(ImageGridEditor, {
      props: {
        modelValue: [
          { colspan: 'col-span-2', image: '/a.jpg', alt: 'Alt A' },
          { colspan: 'col-span-4', image: '/b.jpg', alt: 'Alt B' },
        ],
      },
      global: { stubs },
    });

    const inputs = wrapper.findAll('input[type="text"]');
    expect(inputs.map(i => (i.element as HTMLInputElement).value)).toEqual(['Alt A', 'Alt B']);
  });

  it('editing a tile\'s alt input updates only that image', async () => {
    const modelValue = [
      { colspan: 'col-span-2', image: '/a.jpg', alt: 'Alt A' },
      { colspan: 'col-span-2', image: '/b.jpg', alt: 'Alt B' },
    ];
    const wrapper = mount(ImageGridEditor, {
      props: { modelValue, 'onUpdate:modelValue': (val: unknown) => wrapper.setProps({ modelValue: val }) },
      global: { stubs },
    });

    await wrapper.findAll('input[type="text"]')[1]!.setValue('Updated alt B');

    const emitted = wrapper.emitted('update:modelValue')!;
    const lastEmit = emitted[emitted.length - 1]![0] as any[];
    expect(lastEmit[0]).toEqual(modelValue[0]);
    expect(lastEmit[1]).toMatchObject({ image: '/b.jpg', alt: 'Updated alt B' });
  });

  it('replacing a tile\'s image keeps its position and updates alt/title', async () => {
    const modelValue = [
      { colspan: 'col-span-2', image: '/a.jpg', alt: 'Alt A' },
      { colspan: 'col-span-2', image: '/b.jpg', alt: 'Alt B' },
    ];
    const wrapper = mount(ImageGridEditor, {
      props: { modelValue, 'onUpdate:modelValue': (val: unknown) => wrapper.setProps({ modelValue: val }) },
      global: { stubs },
    });

    // Tile replace buttons are the .image-btn-stub instances wrapping each existing tile
    // (the trailing "add" button is a separate, non-stubbed native button).
    const tileButtons = wrapper.findAll('.image-btn-stub');
    await tileButtons[0]!.trigger('click');

    const emitted = wrapper.emitted('update:modelValue')!;
    const lastEmit = emitted[emitted.length - 1]![0] as any[];
    expect(lastEmit[0]).toMatchObject({ colspan: 'col-span-2', image: '/new.jpg', alt: 'New alt' });
    expect(lastEmit[1]).toEqual(modelValue[1]);
  });

  it('removing a tile removes only that image', async () => {
    const modelValue = [
      { colspan: 'col-span-2', image: '/a.jpg', alt: 'Alt A' },
      { colspan: 'col-span-2', image: '/b.jpg', alt: 'Alt B' },
    ];
    const wrapper = mount(ImageGridEditor, {
      props: { modelValue, 'onUpdate:modelValue': (val: unknown) => wrapper.setProps({ modelValue: val }) },
      global: { stubs },
    });

    // Delete now lives inside each tile's "⋯" hover menu (data-testid="dropdown-menu-content").
    const menus = wrapper.findAll('[data-testid="dropdown-menu-content"]');
    const deleteButton = menus[0]!.findAll('button').find(b => b.text().includes('common.delete'))!;
    await deleteButton.trigger('click');

    expect(wrapper.emitted('update:modelValue')![0]![0]).toEqual([modelValue[1]]);
  });

  it('changing the width via the dropdown updates colspan for that tile only', async () => {
    const modelValue = [
      { colspan: 'col-span-2', image: '/a.jpg', alt: '' },
      { colspan: 'col-span-2', image: '/b.jpg', alt: '' },
    ];
    const wrapper = mount(ImageGridEditor, {
      props: { modelValue, 'onUpdate:modelValue': (val: unknown) => wrapper.setProps({ modelValue: val }) },
      global: { stubs },
    });

    // DropdownMenuItem stub renders a real <button> per option (see commonStubs); the
    // second tile's "1/1" (col-span-full) option is picked.
    const menus = wrapper.findAll('[data-testid="dropdown-menu-content"]');
    const fullWidthOption = menus[1]!.findAll('button').find(b => b.text() === '1/1')!;
    await fullWidthOption.trigger('click');

    const emitted = wrapper.emitted('update:modelValue')!;
    const lastEmit = emitted[emitted.length - 1]![0] as any[];
    expect(lastEmit[0]).toEqual(modelValue[0]);
    expect(lastEmit[1]).toMatchObject({ colspan: 'col-span-full' });
  });

  it('sets a focal point via the tile menu', async () => {
    const modelValue = [{ colspan: 'col-span-2', image: '/a.jpg', alt: '' }];
    const wrapper = mount(ImageGridEditor, {
      props: { modelValue, 'onUpdate:modelValue': (val: unknown) => wrapper.setProps({ modelValue: val }) },
      global: { stubs: { ...stubs, FocalPointPicker: { props: ['imageUrl', 'modelValue'], template: '<button class="focal-point-stub" @click="$emit(\'update:modelValue\', \'10% 20%\')" />' } } },
    });

    const menus = wrapper.findAll('[data-testid="dropdown-menu-content"]');
    const focalPointItem = menus[0]!.findAll('button').find(b => b.text().includes('rich-content.set_focal_point'))!;
    await focalPointItem.trigger('click');
    await wrapper.find('.focal-point-stub').trigger('click');

    const emitted = wrapper.emitted('update:modelValue')!;
    const lastEmit = emitted[emitted.length - 1]![0] as any[];
    expect(lastEmit[0]).toMatchObject({ objectPosition: '10% 20%' });
  });
});
