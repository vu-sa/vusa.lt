import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroCarouselImageHotspot from '../HeroCarouselImageHotspot.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';

import type { HeroCarousel } from '@/Types/contentParts';
import { commonStubs, stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

type Slide = HeroCarousel['json_content'][number];

const stubs = {
  ...commonStubs,
  Popover: stubPopover,
  PopoverAnchor: stubPopoverAnchor,
  PopoverContent: stubPopoverContent,
  ImageSelector: {
    name: 'ImageSelector',
    props: ['showModal', 'selectionType'],
    emits: ['update:showModal', 'submit'],
    template: '<div class="image-selector-stub" :data-open="showModal" />',
  },
  FocalPointPicker: { props: ['imageUrl', 'modelValue'], template: '<div class="focal-point-picker" />' },
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

function makeSlide(overrides: Partial<Slide> = {}): Slide {
  return {
    eyebrow: 'VU SA',
    title: 'Slide 1',
    subtitle: 'Subtitle',
    description: '',
    imageSrc: '',
    imageAlt: '',
    align: 'start',
    buttons: [],
    ...overrides,
  };
}

function mountHotspot(slide: Slide = makeSlide()) {
  const hotspots = useActiveHotspot();
  const wrapper = mount(HeroCarouselImageHotspot, {
    props: {
      slide,
      slideIndex: 0,
      blockKey: 'carousel-1',
    },
    global: {
      stubs,
      provide: { [ACTIVE_HOTSPOT_KEY]: hotspots },
    },
  });
  return { wrapper, hotspots };
}

describe('HeroCarouselImageHotspot', () => {
  it('renders spotlight button and opens popover on click', async () => {
    const { wrapper, hotspots } = mountHotspot();
    const spotlight = wrapper.find('button[data-rc-interactive]');
    expect(spotlight.exists()).toBe(true);

    await spotlight.trigger('click');
    expect(hotspots.isPopoverOpen('carousel-1:slide-0:image')).toBe(true);
  });

  it('renders select_image button and no focal picker when slide has no image', async () => {
    const { wrapper, hotspots } = mountHotspot(makeSlide({ imageSrc: '' }));
    hotspots.openPopover('carousel-1:slide-0:image');
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('rich-content.select_image');
    expect(wrapper.find('.focal-point-picker').exists()).toBe(false);
  });

  it('renders thumbnail, select_image, delete_image and focal picker when slide has an image', async () => {
    const { wrapper, hotspots } = mountHotspot(makeSlide({ imageSrc: '/photo.webp', imageAlt: 'Photo' }));
    hotspots.openPopover('carousel-1:slide-0:image');
    await wrapper.vm.$nextTick();

    expect(wrapper.find('img').attributes('src')).toBe('/photo.webp');
    expect(wrapper.text()).toContain('rich-content.select_image');
    expect(wrapper.text()).toContain('rich-content.delete_image');
    expect(wrapper.find('.focal-point-picker').exists()).toBe(true);
  });

  it('clicking delete_image emits update:slide with empty imageSrc', async () => {
    const { wrapper, hotspots } = mountHotspot(makeSlide({ imageSrc: '/photo.webp' }));
    hotspots.openPopover('carousel-1:slide-0:image');
    await wrapper.vm.$nextTick();

    const deleteBtn = wrapper.findAll('button').find(b => b.text().includes('rich-content.delete_image'));
    expect(deleteBtn).toBeDefined();

    await deleteBtn!.trigger('click');
    const emitted = wrapper.emitted('update:slide');
    expect(emitted).toBeTruthy();
    const updatedSlide = emitted!.at(-1)![0] as Slide;
    expect(updatedSlide.imageSrc).toBe('');
  });

  it('submitting an image from ImageSelector emits update:slide with imageSrc and imageAlt', async () => {
    const { wrapper } = mountHotspot(makeSlide({ imageSrc: '' }));
    const selector = wrapper.findComponent({ name: 'ImageSelector' });

    selector.vm.$emit('submit', { src: '/new-photo.webp', alt: 'New Alt' });
    const emitted = wrapper.emitted('update:slide');
    expect(emitted).toBeTruthy();
    const updatedSlide = emitted!.at(-1)![0] as Slide;
    expect(updatedSlide.imageSrc).toBe('/new-photo.webp');
    expect(updatedSlide.imageAlt).toBe('New Alt');
  });
});
