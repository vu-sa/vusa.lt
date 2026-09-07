import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroCarouselSlideSettingsHotspot from '../HeroCarouselSlideSettingsHotspot.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';

import type { HeroCarousel } from '@/Types/contentParts';
import { commonStubs, stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

type Slide = HeroCarousel['json_content'][number];

const stubs = {
  ...commonStubs,
  Popover: stubPopover,
  PopoverAnchor: stubPopoverAnchor,
  PopoverContent: stubPopoverContent,
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
    imageSrc: '/photo.webp',
    imageAlt: '',
    align: 'start',
    buttons: [],
    ...overrides,
  };
}

function mountHotspot(slide: Slide = makeSlide()) {
  const hotspots = useActiveHotspot();
  const wrapper = mount(HeroCarouselSlideSettingsHotspot, {
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

describe('HeroCarouselSlideSettingsHotspot', () => {
  it('renders spotlight button and opens its own popover, distinct from the image hotspot', async () => {
    const { wrapper, hotspots } = mountHotspot();
    const spotlight = wrapper.find('button[data-rc-interactive]');
    expect(spotlight.exists()).toBe(true);

    await spotlight.trigger('click');
    expect(hotspots.isPopoverOpen('carousel-1:slide-0:settings')).toBe(true);
    expect(hotspots.isPopoverOpen('carousel-1:slide-0:image')).toBe(false);
  });

  it('does not offer right-align as a text position option', async () => {
    const { wrapper, hotspots } = mountHotspot();
    hotspots.openPopover('carousel-1:slide-0:settings');
    await wrapper.vm.$nextTick();

    const options = wrapper.findAll('option').map(o => o.attributes('value'));
    expect(options).toContain('start');
    expect(options).toContain('center');
    expect(options).not.toContain('end');
  });

  it('changing text position emits update:slide', async () => {
    const { wrapper, hotspots } = mountHotspot();
    hotspots.openPopover('carousel-1:slide-0:settings');
    await wrapper.vm.$nextTick();

    const selects = wrapper.findAll('select');
    await selects[0]!.setValue('center');
    const emitted = wrapper.emitted('update:slide');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as Slide).align).toBe('center');
  });

  it('offers four scrim strengths and clears the override when "default" is chosen', async () => {
    const { wrapper, hotspots } = mountHotspot(makeSlide({ scrim: 'strong' }));
    hotspots.openPopover('carousel-1:slide-0:settings');
    await wrapper.vm.$nextTick();

    const scrimSelect = wrapper.findAll('select')[1]!;
    const options = scrimSelect.findAll('option').map(o => o.attributes('value'));
    expect(options).toEqual(['default', 'light', 'medium', 'strong', 'dark']);

    await scrimSelect.setValue('default');
    const emitted = wrapper.emitted('update:slide');
    expect((emitted!.at(-1)![0] as Slide).scrim).toBeUndefined();
  });

  it('hides the scrim field when the slide has no image', async () => {
    const { wrapper, hotspots } = mountHotspot(makeSlide({ imageSrc: '' }));
    hotspots.openPopover('carousel-1:slide-0:settings');
    await wrapper.vm.$nextTick();

    expect(wrapper.findAll('select')).toHaveLength(1);
  });
});
