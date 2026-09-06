import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroEditableElement from '../HeroEditableElement.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';
import type { Hero } from '@/Types/contentParts';
import { commonStubs, stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

function makeElement(overrides: Partial<Hero['options']> = {}, jsonOverrides: Partial<Hero['json_content']> = {}): Hero {
  return {
    json_content: {
      title: 'Prisijunk',
      description: 'Aprašymas',
      imageSrc: '/img.jpg',
      imageAlt: 'Alt',
      buttons: [{ text: 'Registruotis', link: '#' }],
      ...jsonOverrides,
    },
    options: { textLeft: true, ...overrides },
  };
}

describe('HeroEditableElement (full-screen editor)', () => {
  const editableStubs = {
    ...commonStubs,
    Popover: stubPopover,
    PopoverAnchor: stubPopoverAnchor,
    PopoverContent: stubPopoverContent,
    ImageWithDecorations: { template: '<div class="image-with-decorations" />' },
    SmartLink: { template: '<a><slot /></a>' },
    RCIcon: { props: ['name'], template: '<span class="rc-icon" />' },
    RCIconSelect: { props: ['modelValue', 'allowNone'], template: '<div class="rc-icon-select" />' },
    TiptapImageButton: { template: '<button class="tiptap-image-button"><slot /></button>' },
    FocalPointPicker: { props: ['imageUrl', 'modelValue'], template: '<div class="focal-point-picker" />' },
    RCDecorationListEditor: { props: ['modelValue'], template: '<div class="decoration-list-editor" />' },
    TiptapEditor: {
      props: ['modelValue', 'preset', 'html', 'placeholder', 'toolbar', 'showBold'],
      emits: ['update:modelValue'],
      template: '<div class="tiptap-editor-stub" :data-toolbar="toolbar" :data-show-bold="showBold"><input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></div>',
    },
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

  function mountEditable(element: Hero) {
    const hotspots = useActiveHotspot();
    const wrapper = mount(HeroEditableElement, {
      props: { element, blockKey: 'hero-1' },
      global: { stubs: editableStubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
    });
    return { wrapper, hotspots };
  }

  it('(a) eyebrow remains a plain inline field and bubbles update:element on edit', async () => {
    const { wrapper } = mountEditable(makeElement({}, { eyebrow: 'Prisijunk prie mūsų' }));
    const eyebrow = wrapper.find('[contenteditable]');
    expect(eyebrow.exists()).toBe(true);

    eyebrow.element.textContent = 'Naujas antakis';
    await eyebrow.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as Hero).json_content.eyebrow).toBe('Naujas antakis');
  });

  it('(a special case) clicking the static title claims the hotspot and swaps in a live TiptapEditor', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement());

    expect(wrapper.find('.tiptap-editor-stub').exists()).toBe(false);

    await wrapper.find('[role="heading"] button').trigger('click');

    expect(hotspots.isTextFieldLive('hero-1:title')).toBe(true);
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.tiptap-editor-stub').exists()).toBe(true);
    expect(wrapper.find('.tiptap-editor-stub').classes()).toContain('rc-hero-title-editor');
    expect(wrapper.find('.tiptap-editor-stub').classes()).toContain('u-display');
    expect(wrapper.find('[role="heading"]').exists()).toBe(false);
  });

  it('editing the live title emits update:element with the patched title', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement());
    hotspots.openTextField('hero-1:title');
    await wrapper.vm.$nextTick();

    await wrapper.find('.tiptap-editor-stub input').setValue('<strong>Naujas</strong>');
    const emitted = wrapper.emitted('update:element');
    expect((emitted!.at(-1)![0] as Hero).json_content.title).toBe('<strong>Naujas</strong>');
  });

  it('opens a compact rich-text editor for descriptions, including bold, italic, and underline controls', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement());

    await wrapper.find('p.max-w-lg button').trigger('click');

    expect(hotspots.isTextFieldLive('hero-1:description')).toBe(true);
    const editor = wrapper.get('.tiptap-editor-stub');
    expect(editor.attributes('data-toolbar')).toBe('bubble');
    expect(editor.attributes('data-show-bold')).toBeUndefined();
  });

  it('shows a title placeholder and uses the floating toolbar when an editable title is empty', async () => {
    const { wrapper } = mountEditable(makeElement({}, { title: '' }));
    expect(wrapper.find('[role="heading"]').text()).toContain('rich-content.title');

    await wrapper.find('[role="heading"] button').trigger('click');
    expect(wrapper.find('.tiptap-editor-stub').attributes('data-toolbar')).toBe('bubble');
  });

  it('keeps split titles left-aligned and centered titles centered while inactive', () => {
    const { wrapper: splitWrapper } = mountEditable(makeElement({ variant: 'split' }));
    const { wrapper } = mountEditable(makeElement({ variant: 'centered' }));

    expect(splitWrapper.get('[role="heading"]').classes()).toContain('text-left');
    expect(wrapper.get('[role="heading"]').classes()).toContain('text-center');
  });

  it('keeps the title uppercase and centered after the live editor closes', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement({ variant: 'centered' }, { title: '<p>Prisijunk</p>' }));

    await wrapper.get('[role="heading"] button').trigger('click');
    expect(wrapper.get('.tiptap-editor-stub').attributes('data-show-bold')).toBe('false');

    hotspots.close('hero-1:title');
    await wrapper.vm.$nextTick();

    const heading = wrapper.get('[role="heading"]');
    expect(heading.classes()).toContain('uppercase');
    expect(heading.classes()).toContain('text-center');
    expect(heading.find('p').text()).toBe('Prisijunk');
  });

  it('does not pull the first split hero beneath the editor navbar', () => {
    const { wrapper } = mountEditable(makeElement({ variant: 'split' }));

    expect(wrapper.get('section').classes()).not.toContain('-mt-4');
  });

  it('(b) the image hotspot bubbles update:content up as update:element', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement());
    hotspots.openPopover('hero-1:image');
    await wrapper.vm.$nextTick();

    const deleteBtn = wrapper.findAll('button').find(b => b.text() === 'rich-content.delete_image');
    await deleteBtn!.trigger('click');

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as Hero).json_content.imageSrc).toBe('');
  });

  it('(c) an empty buttons array renders the add-button placeholder instead of HeroButtons', () => {
    const { wrapper } = mountEditable(makeElement({}, { buttons: [] }));
    expect(wrapper.text()).toContain('rich-content.add_button');
  });
});
