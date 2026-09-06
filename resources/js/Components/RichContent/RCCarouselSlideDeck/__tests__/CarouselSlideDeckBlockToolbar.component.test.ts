import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import CarouselSlideDeckBlockToolbar from '../CarouselSlideDeckBlockToolbar.vue';
import type { ContentPart } from '../../Types';

const stubs = {
  RCBlockToolbarShell: {
    props: ['content', 'blockKey', 'reference', 'canMoveUp', 'canMoveDown', 'canDelete'],
    emits: ['move-up', 'move-down', 'delete', 'open-form'],
    template: '<div class="shell-stub"><slot /></div>',
  },
  RCSectionToolbarOptions: {
    props: ['modelValue', 'presentationDisabled'],
    emits: ['update:modelValue'],
    template: '<div class="section-toolbar-stub" />',
  },
  RCWidthPicker: {
    props: ['modelValue', 'allowedWidths'],
    emits: ['update:modelValue'],
    template: '<div class="width-picker-stub" />',
  },
  ImageSelector: {
    props: ['showModal', 'selectionType'],
    emits: ['update:showModal', 'submit'],
    template: '<div v-if="showModal" class="image-selector-stub" />',
  },
};

function makeContent(slidesCount = 1, options: Record<string, unknown> = {}): ContentPart {
  const slides = Array.from({ length: slidesCount }, (_, i) => ({
    icon: 'info',
    badge: `Badge ${i + 1}`,
    title: `Slide ${i + 1}`,
    description: '',
    imageSrc: `/slide-${i + 1}.webp`,
    imageAlt: '',
    imageLeft: false,
    decorations: [],
  }));

  return {
    type: 'carousel-slide-deck',
    json_content: slides,
    options: {
      autoplay: true,
      autoplayDelay: 8000,
      showNavigation: true,
      showThumbnails: true,
      ...options,
    },
  };
}

function mountToolbar(content: ContentPart) {
  return mount(CarouselSlideDeckBlockToolbar, {
    props: {
      content,
      blockKey: 'carousel-slide-deck-1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
    },
    global: { stubs },
  });
}

describe('CarouselSlideDeckBlockToolbar', () => {
  it('displays slide count and add slide button', () => {
    const wrapper = mountToolbar(makeContent(2));
    expect(wrapper.text()).toContain('rich-content.slides (2)');
    expect(wrapper.find('[data-rc-toolbar-add-slide]').exists()).toBe(true);
  });

  it('clicking add slide emits update:content with an extra slide', async () => {
    const wrapper = mountToolbar(makeContent(1));
    await wrapper.get('[data-rc-toolbar-add-slide]').trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updatedContent = emitted!.at(-1)![0] as ContentPart;
    expect(updatedContent.json_content).toHaveLength(2);
  });

  it('moves slide order using move buttons', async () => {
    const wrapper = mountToolbar(makeContent(2));
    const moveDownBtn = wrapper.findAll('button[title="rich-content.move_down"]')[0]!;
    await moveDownBtn.trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updatedContent = emitted!.at(-1)![0] as ContentPart;
    expect(updatedContent.json_content.map((s: any) => s.title)).toEqual(['Slide 2', 'Slide 1']);
  });

  it('removes a slide when slides > 1', async () => {
    const wrapper = mountToolbar(makeContent(2));
    const removeBtn = wrapper.findAll('button[title="rich-content.remove_slide"]')[0]!;
    await removeBtn.trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updatedContent = emitted!.at(-1)![0] as ContentPart;
    expect(updatedContent.json_content).toHaveLength(1);
    expect(updatedContent.json_content[0].title).toBe('Slide 2');
  });

  it('renders section toolbar options', () => {
    const wrapper = mountToolbar(makeContent(1));
    expect(wrapper.find('.section-toolbar-stub').exists()).toBe(true);
  });
});
