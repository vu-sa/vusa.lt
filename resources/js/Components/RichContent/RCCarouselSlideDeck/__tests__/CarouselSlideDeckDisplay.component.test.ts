import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import CarouselSlideDeckDisplay from '../CarouselSlideDeckDisplay.vue';
import { waitForSelector } from '@/tests/helpers/waitForSelector';
import type { CarouselSlideDeck } from '@/Types/contentParts';

function makeElement(slides: Partial<CarouselSlideDeck['json_content'][number]>[] = []): CarouselSlideDeck {
  return {
    json_content: slides.map(slide => ({
      icon: 'info',
      badge: 'Badge',
      title: 'Title',
      description: '',
      imageSrc: '',
      imageAlt: '',
      imageLeft: false,
      decorations: [],
      ...slide,
    })),
    options: {
      autoplay: false,
      showNavigation: true,
      showThumbnails: true,
    },
  };
}

const stubs = {
  Carousel: { template: '<div class="carousel-stub"><slot /></div>' },
  CarouselContent: { template: '<div class="carousel-content-stub"><slot /></div>' },
  CarouselItem: { template: '<div class="carousel-item-stub"><slot /></div>' },
  CarouselNext: { template: '<button class="carousel-next-stub" />' },
  CarouselPrevious: { template: '<button class="carousel-prev-stub" />' },
  RCSection: {
    props: ['title', 'subtitle', 'eyebrow', 'editable'],
    template: '<div class="section-stub"><slot /></div>',
  },
  RCIcon: {
    props: ['name'],
    template: '<div class="icon-stub" />',
  },
  RCIconSelect: {
    props: ['modelValue'],
    template: '<div class="icon-select-stub" />',
  },
  ImageWithDecorations: {
    props: ['src', 'alt'],
    template: '<div class="image-decorations-stub" />',
  },
  ImageSelector: {
    props: ['showModal'],
    template: '<div v-if="showModal" class="image-selector-stub" />',
  },
  Dialog: {
    props: ['open'],
    template: '<div v-if="open" class="dialog-stub"><slot /></div>',
  },
  DialogContent: { template: '<div><slot /></div>' },
  DialogHeader: { template: '<div><slot /></div>' },
  DialogTitle: { template: '<div><slot /></div>' },
};

describe('CarouselSlideDeckDisplay — public', () => {
  it('renders slide title and badge as non-editable text', () => {
    const wrapper = mount(CarouselSlideDeckDisplay, {
      props: {
        element: makeElement([{ title: 'Skaidrė 1', badge: 'Kategorija' }]),
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Skaidrė 1');
    expect(wrapper.text()).toContain('Kategorija');
    expect(wrapper.find('[contenteditable]').exists()).toBe(false);
  });
});

describe('CarouselSlideDeckDisplay — editable', () => {
  it('renders title and badge as inline-editable elements', async () => {
    const wrapper = mount(CarouselSlideDeckDisplay, {
      props: {
        element: makeElement([{ title: 'Redaguojama', badge: 'Žyma' }]),
        editable: true,
        blockKey: 'carousel-1',
      },
      global: { stubs },
    });
    // RCInlineText is lazy-loaded (see CarouselSlideDeckDisplay.vue) — resolving that
    // dynamic import needs a wait before the editable markup it renders exists.
    await waitForSelector(wrapper, '[contenteditable]');

    const editableEls = wrapper.findAll('[contenteditable]');
    expect(editableEls.length).toBeGreaterThanOrEqual(2);
    expect(wrapper.text()).toContain('Redaguojama');
    expect(wrapper.text()).toContain('Žyma');
  });

  it('renders zero-slides empty state with add button when slides are empty and editable', () => {
    const wrapper = mount(CarouselSlideDeckDisplay, {
      props: {
        element: makeElement([]),
        editable: true,
        blockKey: 'carousel-1',
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('rich-content.no_slides');
  });

  it('shows remove slide button when multiple slides exist', () => {
    const wrapper = mount(CarouselSlideDeckDisplay, {
      props: {
        element: makeElement([{ title: 'S1' }, { title: 'S2' }]),
        editable: true,
        blockKey: 'carousel-1',
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-rc-carousel-remove-slide]').exists()).toBe(true);
  });
});
