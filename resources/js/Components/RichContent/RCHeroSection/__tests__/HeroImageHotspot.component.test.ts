import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroImageHotspot from '../HeroImageHotspot.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';
import type { Hero } from '@/Types/contentParts';
import { commonStubs, stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  Popover: stubPopover,
  PopoverAnchor: stubPopoverAnchor,
  PopoverContent: stubPopoverContent,
  ImageWithDecorations: { template: '<div class="image-with-decorations" />' },
  TiptapImageButton: { template: '<button class="tiptap-image-button"><slot /></button>' },
  FocalPointPicker: { props: ['imageUrl', 'modelValue'], template: '<div class="focal-point-picker" />' },
  RCDecorationListEditor: { props: ['modelValue'], template: '<div class="decoration-list-editor" />' },
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

function makeHero(overrides: Partial<Hero['options']> = {}, jsonOverrides: Partial<Hero['json_content']> = {}): Hero {
  return {
    json_content: {
      title: 'T', description: 'D', imageSrc: '', imageAlt: '', buttons: [],
      ...jsonOverrides,
    },
    options: { variant: 'split', ...overrides },
  };
}

function mountHotspot(content: Hero) {
  const hotspots = useActiveHotspot();
  const wrapper = mount(HeroImageHotspot, {
    props: { content, blockKey: 'hero-1' },
    global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
  });
  return { wrapper, hotspots };
}

describe('HeroImageHotspot', () => {
  it('renders the add placeholder when imageSrc is empty', () => {
    const { wrapper } = mountHotspot(makeHero());
    expect(wrapper.text()).toContain('rich-content.add_image');
    expect(wrapper.find('.image-with-decorations').exists()).toBe(false);
  });

  it('renders ImageWithDecorations for the split variant when an image is set', () => {
    const { wrapper } = mountHotspot(makeHero({ variant: 'split' }, { imageSrc: '/img.jpg' }));
    expect(wrapper.find('.image-with-decorations').exists()).toBe(true);
  });

  it('renders a plain thumbnail img for the panel variant when an image is set', () => {
    const { wrapper } = mountHotspot(makeHero({ variant: 'panel' }, { imageSrc: '/img.jpg' }));
    expect(wrapper.find('.image-with-decorations').exists()).toBe(false);
    expect(wrapper.find('img').attributes('src')).toBe('/img.jpg');
  });

  it('opens the popover with id `${blockKey}:image` when clicked', async () => {
    const { wrapper, hotspots } = mountHotspot(makeHero());

    await wrapper.find('[data-rc-interactive]').trigger('click');

    expect(hotspots.isPopoverOpen('hero-1:image')).toBe(true);
  });

  it('renders separate image, overlay, and decoration spotlights', () => {
    const { wrapper } = mountHotspot(makeHero({ variant: 'split' }, { imageSrc: '/img.jpg' }));

    const rail = wrapper.get('[data-testid="hero-image-spotlight-rail"]');
    expect(rail.findAll('[data-rc-interactive]')).toHaveLength(3);
    expect(rail.element.parentElement?.classList.contains('grid-cols-[minmax(0,1fr)_1.5rem]')).toBe(true);
  });

  it('shows only the image picker when no image is set yet', async () => {
    const { wrapper, hotspots } = mountHotspot(makeHero());
    hotspots.openPopover('hero-1:image');
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.tiptap-image-button').exists()).toBe(true);
    expect(wrapper.find('.focal-point-picker').exists()).toBe(false);
    expect(wrapper.find('.decoration-list-editor').exists()).toBe(false);
  });

  it('keeps decorations out of the image settings form', async () => {
    const splitHero = makeHero({ variant: 'split' }, { imageSrc: '/img.jpg' });
    const { wrapper: splitWrapper, hotspots: splitHotspots } = mountHotspot(splitHero);
    splitHotspots.openPopover('hero-1:image');
    await splitWrapper.vm.$nextTick();
    expect(splitWrapper.find('.focal-point-picker').exists()).toBe(true);
    expect(splitWrapper.find('.decoration-list-editor').exists()).toBe(false);

    const panelHero = makeHero({ variant: 'panel' }, { imageSrc: '/img.jpg' });
    const { wrapper: panelWrapper, hotspots: panelHotspots } = mountHotspot(panelHero);
    panelHotspots.openPopover('hero-1:image');
    await panelWrapper.vm.$nextTick();
    expect(panelWrapper.find('.focal-point-picker').exists()).toBe(true);
    expect(panelWrapper.find('.decoration-list-editor').exists()).toBe(false);
  });

  it('opens image, overlay, and decoration settings from separate spotlights', async () => {
    const { wrapper, hotspots } = mountHotspot(makeHero({ variant: 'split' }, { imageSrc: '/img.jpg' }));

    hotspots.openPopover('hero-1:image');
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).not.toContain('rich-content.overlay_title');

    hotspots.openPopover('hero-1:overlay');
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('rich-content.overlay_title');

    hotspots.openPopover('hero-1:decorations');
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.decoration-list-editor').exists()).toBe(true);
    expect(wrapper.find('.focal-point-picker').exists()).toBe(false);
  });

  it('lazily initializes a missing overlayContent for split without crashing', () => {
    const hero = makeHero({ variant: 'split' }, { imageSrc: '/img.jpg' });
    delete (hero.json_content as { overlayContent?: unknown }).overlayContent;
    const { wrapper } = mountHotspot(hero);
    expect(wrapper.emitted('update:content')).toBeTruthy();
    const patched = wrapper.emitted('update:content')!.at(-1)![0] as Hero;
    expect(patched.json_content.overlayContent).toEqual({ title: '', subtitle: '' });
  });

  it('emits update:content with the deleted imageSrc when "delete image" is pressed', async () => {
    const { wrapper, hotspots } = mountHotspot(makeHero({}, { imageSrc: '/img.jpg' }));
    hotspots.openPopover('hero-1:image');
    await wrapper.vm.$nextTick();
    const deleteBtn = wrapper.findAll('button').find(b => b.text() === 'rich-content.delete_image');
    await deleteBtn!.trigger('click');
    const emitted = wrapper.emitted('update:content');
    expect((emitted!.at(-1)![0] as Hero).json_content.imageSrc).toBe('');
  });
});
