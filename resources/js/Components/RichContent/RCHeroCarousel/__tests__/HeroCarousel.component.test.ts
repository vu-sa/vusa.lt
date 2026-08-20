import { describe, expect, it, vi } from 'vitest';
import { mount, type VueWrapper } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroCarouselDisplay from '../HeroCarouselDisplay.vue';
import HeroCarouselEditor from '../../Types/HeroCarouselEditor.vue';
import { createContentItem } from '../../Types';
import type { HeroCarousel } from '@/Types/contentParts';

/**
 * Wiring-level coverage for the hero carousel display. The embla scroll/autoplay
 * behavior is not exercised: the Carousel* family is stubbed (jsdom + embla's
 * layout measurements are unreliable — same precedent as the carousel-slide-deck
 * tests in __tests__/newBlockTypes.component.test.ts).
 */

function makeElement(
  jsonOverrides: Partial<HeroCarousel['json_content'][number]>[] = [],
  options: Partial<HeroCarousel['options']> = {},
): HeroCarousel {
  return {
    json_content: [
      {
        eyebrow: 'VU SA kviečia',
        title: 'Pirmasis',
        subtitle: 'Paantraštė',
        description: { type: 'doc', content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Aprašymas' }] }] },
        imageSrc: '/img-1.webp',
        imageAlt: 'Alt 1',
        align: 'start',
        buttons: [{ text: 'Tapk nariu', link: '/lt/narys', variant: 'default', color: 'red' }],
        ...jsonOverrides[0],
      },
      {
        title: 'Antrasis',
        description: { type: 'doc', content: [] },
        imageSrc: '/img-2.webp',
        imageAlt: 'Alt 2',
        ...jsonOverrides[1],
      },
    ],
    options: { autoplay: false, autoplayDelay: 8000, showArrows: true, showIndicators: true, scrim: 'medium', ...options },
  };
}

const stubs = {
  Carousel: { template: '<div class="carousel-stub"><slot /></div>' },
  CarouselContent: { template: '<div><slot /></div>' },
  CarouselItem: { template: '<div class="carousel-item-stub"><slot /></div>' },
  CarouselPrevious: { template: '<button class="carousel-prev" />' },
  CarouselNext: { template: '<button class="carousel-next" />' },
  SmartLink: { template: '<a :href="href"><slot /></a>' },
  teleport: true,
};

function findButtonByText(wrapper: VueWrapper, key: string) {
  const match = wrapper.findAll('button').find(b => b.text().includes(key));
  if (!match) throw new Error(`No button found containing text "${key}"`);
  return match;
}

describe('HeroCarouselDisplay', () => {
  it('renders slide eyebrow, title as h2, subtitle, description and buttons', () => {
    const wrapper = mount(HeroCarouselDisplay, { props: { element: makeElement() }, global: { stubs } });

    expect(wrapper.findAll('h2')).toHaveLength(2);
    expect(wrapper.find('h2').text()).toBe('Pirmasis');
    expect(wrapper.text()).toContain('VU SA kviečia');
    expect(wrapper.text()).toContain('Paantraštė');
    expect(wrapper.text()).toContain('Aprašymas');

    const link = wrapper.findAll('a').find(a => a.text().includes('Tapk nariu'));
    expect(link?.attributes('href')).toBe('/lt/narys');
  });

  it('renders the first slide image eager with high fetchpriority, the rest lazy', () => {
    const wrapper = mount(HeroCarouselDisplay, { props: { element: makeElement() }, global: { stubs } });

    const images = wrapper.findAll('img');
    expect(images[0]!.attributes('loading')).toBe('eager');
    expect(images[0]!.attributes('fetchpriority')).toBe('high');
    expect(images[1]!.attributes('loading')).toBe('lazy');
  });

  it('marks non-active slides inert and applies per-slide text alignment classes', () => {
    const wrapper = mount(HeroCarouselDisplay, {
      props: { element: makeElement([{ align: 'center' as const }]) },
      global: { stubs },
    });

    const items = wrapper.findAll('.carousel-item-stub');
    expect(items[0]!.attributes('inert')).toBeUndefined();
    expect(items[1]!.attributes('inert')).toBeDefined();

    const textBlocks = items.map(item => item.find('.relative.z-10'));
    expect(textBlocks[0]!.classes()).toContain('items-center');
    expect(textBlocks[0]!.classes()).toContain('justify-center');
    expect(textBlocks[1]!.classes()).toContain('items-start');
  });

  it('applies the scrim strength option to the overlay layer', () => {
    const light = mount(HeroCarouselDisplay, { props: { element: makeElement([], { scrim: 'light' }) }, global: { stubs } });
    expect(light.html()).toContain('bg-zinc-950/20');

    const dark = mount(HeroCarouselDisplay, { props: { element: makeElement([], { scrim: 'dark' }) }, global: { stubs } });
    expect(dark.html()).toContain('bg-zinc-950/60');
  });

  it('renders one labelled dot per slide with aria-current on the active one', () => {
    const wrapper = mount(HeroCarouselDisplay, { props: { element: makeElement() }, global: { stubs } });

    // $t is mocked to return the key verbatim (tests/setup.ts).
    const dots = wrapper.findAll('button[aria-label="accessibility.carousel_go_to_slide"]');
    expect(dots).toHaveLength(2);
    expect(dots[0]!.attributes('aria-current')).toBe('true');
    expect(dots[1]!.attributes('aria-current')).toBeUndefined();
  });

  it('insets the photos in a rounded panel while the section stays full-bleed', () => {
    const wrapper = mount(HeroCarouselDisplay, { props: { element: makeElement() }, global: { stubs } });

    expect(wrapper.find('.overflow-hidden.rounded-2xl').exists()).toBe(true);
    // Page-gutter padding on the section keeps the panel off the viewport edges and
    // gives the straddling arrows room.
    expect(wrapper.find('section').classes()).toContain('px-4');
  });

  it('renders dot indicators below the panel instead of over the photos', () => {
    const wrapper = mount(HeroCarouselDisplay, { props: { element: makeElement() }, global: { stubs } });

    const dotsRow = wrapper.find('.mt-3.flex.justify-center');
    expect(dotsRow.exists()).toBe(true);
    expect(dotsRow.findAll('button[aria-label="accessibility.carousel_go_to_slide"]')).toHaveLength(2);
  });

  it('applies the default md panel height and honors the height option', () => {
    const defaultHeight = mount(HeroCarouselDisplay, { props: { element: makeElement() }, global: { stubs } });
    expect(defaultHeight.find('.carousel-item-stub div').classes()).toContain('h-[55svh]');

    const small = mount(HeroCarouselDisplay, { props: { element: makeElement([], { height: 'sm' }) }, global: { stubs } });
    expect(small.find('.carousel-item-stub div').classes()).toContain('h-[42svh]');

    const large = mount(HeroCarouselDisplay, { props: { element: makeElement([], { height: 'lg' }) }, global: { stubs } });
    expect(large.find('.carousel-item-stub div').classes()).toContain('h-[68svh]');
  });

  it('hides arrows and dots when there is only one slide', () => {
    const element = makeElement();
    element.json_content = [element.json_content[0]!];
    const wrapper = mount(HeroCarouselDisplay, { props: { element }, global: { stubs } });

    expect(wrapper.find('.carousel-prev').exists()).toBe(false);
    expect(wrapper.find('.carousel-next').exists()).toBe(false);
    expect(wrapper.findAll('button[aria-label="accessibility.carousel_go_to_slide"]')).toHaveLength(0);
  });

  it('reads FormData-mangled "0" strings as off (legacy rows saved via forceFormData)', () => {
    const wrapper = mount(HeroCarouselDisplay, {
      props: { element: makeElement([], { showArrows: '0' as unknown as boolean, showIndicators: '0' as unknown as boolean }) },
      global: { stubs },
    });

    // Plain truthiness would see "0" as truthy and wrongly render both.
    expect(wrapper.find('.carousel-prev').exists()).toBe(false);
    expect(wrapper.find('.carousel-next').exists()).toBe(false);
    expect(wrapper.findAll('button[aria-label="accessibility.carousel_go_to_slide"]')).toHaveLength(0);
  });

  it('renders an empty deck without throwing', () => {
    const element = makeElement();
    element.json_content = [];
    const wrapper = mount(HeroCarouselDisplay, { props: { element }, global: { stubs } });
    expect(wrapper.exists()).toBe(true);
  });

  it('applies the navbar pull-up margin only as the first element', () => {
    const withMargin = mount(HeroCarouselDisplay, { props: { element: makeElement(), isFirstElement: true }, global: { stubs } });
    expect(withMargin.find('section').classes()).toContain('-mt-8');

    const withoutMargin = mount(HeroCarouselDisplay, { props: { element: makeElement(), isFirstElement: false }, global: { stubs } });
    expect(withoutMargin.find('section').classes()).not.toContain('-mt-8');
  });

  it('anchors the section for ToC scroll targets', () => {
    const wrapper = mount(HeroCarouselDisplay, { props: { element: makeElement(), anchorId: 42 }, global: { stubs } });
    expect(wrapper.find('#rc-42').exists()).toBe(true);
  });
});

describe('HeroCarouselEditor', () => {
  it('adds a slide via v-model', async () => {
    const item = createContentItem('hero-carousel');
    const wrapper = mount(HeroCarouselEditor, {
      props: { modelValue: item.json_content, options: item.options },
      global: { stubs: { TiptapEditor: true } },
    });

    await findButtonByText(wrapper, 'add_first_slide').trigger('click');
    const emitted = wrapper.emitted('update:modelValue')?.at(-1)?.[0] as HeroCarousel['json_content'];
    expect(emitted).toHaveLength(1);
    expect(emitted[0]).toMatchObject({ title: '', align: 'start', buttons: [] });
  });

  it('shows switches checked/unchecked from FormData-mangled "1"/"0" strings (legacy rows)', async () => {
    // The editor mutates the options object in place (defineModel only emits on
    // whole-value replacement), so assert against the same reference it holds.
    const options: HeroCarousel['options'] = {
      autoplay: '1',
      autoplayDelay: '8000',
      showArrows: '1',
      showIndicators: '0',
      scrim: 'medium',
    } as unknown as HeroCarousel['options'];

    const wrapper = mount(HeroCarouselEditor, {
      props: { modelValue: [], options },
      global: { stubs: { TiptapEditor: true } },
    });

    const switches = wrapper.findAll('[role="switch"]');
    expect(switches).toHaveLength(3);
    expect(switches[0]!.attributes('aria-checked')).toBe('true');
    expect(switches[1]!.attributes('aria-checked')).toBe('true');
    expect(switches[2]!.attributes('aria-checked')).toBe('false');

    // Toggling must write a real boolean back into the options object.
    await switches[2]!.trigger('click');
    expect(options.showIndicators).toBe(true);
  });

  it('adds a button to an existing slide via v-model', async () => {
    const wrapper = mount(HeroCarouselEditor, {
      props: {
        modelValue: [{ eyebrow: '', title: 'T', subtitle: '', description: { type: 'doc', content: [] }, imageSrc: '/x.webp', imageAlt: '', align: 'start', buttons: [] }],
        options: { autoplay: true, autoplayDelay: 8000, showArrows: true, showIndicators: true, scrim: 'medium' },
      },
      global: { stubs: { TiptapEditor: true } },
    });

    await findButtonByText(wrapper, 'add_first_button').trigger('click');
    const emitted = wrapper.emitted('update:modelValue')?.at(-1)?.[0] as HeroCarousel['json_content'];
    expect(emitted[0]!.buttons).toHaveLength(1);
    expect(emitted[0]!.buttons![0]).toMatchObject({ variant: 'default', color: 'red' });
  });
});
