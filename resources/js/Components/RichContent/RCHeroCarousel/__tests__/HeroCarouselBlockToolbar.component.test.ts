import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroCarouselBlockToolbar from '../HeroCarouselBlockToolbar.vue';
import type { ContentPart } from '../../Types';

const stubs = {
  RCBlockToolbarShell: {
    props: ['content', 'blockKey', 'reference', 'canMoveUp', 'canMoveDown', 'canDelete'],
    emits: ['move-up', 'move-down', 'delete', 'open-form'],
    template: '<div class="shell-stub"><slot /></div>',
  },
};

function makeContent(slidesCount = 1, options: Record<string, unknown> = {}): ContentPart {
  const slides = Array.from({ length: slidesCount }, (_, i) => ({
    eyebrow: `Eyebrow ${i + 1}`,
    title: `Slide ${i + 1}`,
    subtitle: '',
    description: '',
    imageSrc: `/img-${i + 1}.webp`,
    imageAlt: '',
    align: 'start' as const,
    buttons: [],
  }));

  return {
    type: 'hero-carousel',
    json_content: slides,
    options: {
      height: 'md',
      scrim: 'medium',
      showArrows: true,
      showIndicators: true,
      autoplay: false,
      autoplayDelay: 8000,
      ...options,
    },
  };
}

function mountToolbar(content: ContentPart) {
  return mount(HeroCarouselBlockToolbar, {
    props: {
      content,
      blockKey: 'hero-carousel-1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
    },
    global: { stubs },
  });
}

describe('HeroCarouselBlockToolbar', () => {
  it('displays the slide count and an add slide button', () => {
    const wrapper = mountToolbar(makeContent(2));
    expect(wrapper.text()).toContain('rich-content.slides (2)');

    const addBtn = wrapper.findAll('button').find(b => b.text().includes('rich-content.add_slide'));
    expect(addBtn).toBeDefined();
  });

  it('clicking add slide emits update:content with an extra slide', async () => {
    const wrapper = mountToolbar(makeContent(1));
    const addBtn = wrapper.findAll('button').find(b => b.text().includes('rich-content.add_slide'));

    await addBtn!.trigger('click');
    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updatedContent = emitted!.at(-1)![0] as ContentPart;
    expect(updatedContent.json_content).toHaveLength(2);
  });

  it('hides multi-slide controls when there is only one slide', () => {
    const wrapper = mountToolbar(makeContent(1));
    expect(wrapper.text()).not.toContain('rich-content.show_arrows');
    expect(wrapper.text()).not.toContain('rich-content.show_indicators');
    expect(wrapper.text()).not.toContain('rich-content.enable_autoplay');
  });

  it('shows multi-slide controls when there are 2 or more slides', () => {
    const wrapper = mountToolbar(makeContent(2));
    expect(wrapper.text()).toContain('rich-content.show_arrows');
    expect(wrapper.text()).toContain('rich-content.show_indicators');
    expect(wrapper.text()).toContain('rich-content.enable_autoplay');
  });

  it('shows autoplay delay input only when autoplay is enabled', async () => {
    const withoutAutoplay = mountToolbar(makeContent(2, { autoplay: false }));
    expect(withoutAutoplay.text()).not.toContain('rich-content.autoplay_delay');

    const withAutoplay = mountToolbar(makeContent(2, { autoplay: true }));
    expect(withAutoplay.text()).toContain('rich-content.autoplay_delay');
    expect(withAutoplay.find('input[type="number"]').exists()).toBe(true);
  });

  it('renders slide items with titles and allows removing a slide when slides > 1', async () => {
    const wrapper = mountToolbar(makeContent(2));
    expect(wrapper.text()).toContain('Slide 1');
    expect(wrapper.text()).toContain('Slide 2');

    const removeButtons = wrapper.findAll('button[title="rich-content.remove_slide"]');
    expect(removeButtons).toHaveLength(2);

    await removeButtons[0]!.trigger('click');
    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updatedContent = emitted!.at(-1)![0] as ContentPart;
    expect(updatedContent.json_content).toHaveLength(1);
    expect(updatedContent.json_content[0].title).toBe('Slide 2');
  });

  it('toggling showArrows switch emits update:content with updated showArrows', async () => {
    const wrapper = mountToolbar(makeContent(2, { showArrows: true }));
    const switches = wrapper.findAll('[role="switch"]');
    // switches: [showArrows, showIndicators, autoplay]
    expect(switches[0]!.attributes('aria-checked')).toBe('true');

    await switches[0]!.trigger('click');
    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updatedContent = emitted!.at(-1)![0] as ContentPart;
    expect(updatedContent.options?.showArrows).toBe(false);
  });
});
