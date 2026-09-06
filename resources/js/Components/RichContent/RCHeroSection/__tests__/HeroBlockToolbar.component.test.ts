import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroBlockToolbar from '../HeroBlockToolbar.vue';
import type { ContentPart } from '../../Types';

const stubs = {
  RCBlockToolbarShell: {
    props: ['content', 'blockKey', 'reference', 'canMoveUp', 'canMoveDown', 'canDelete'],
    emits: ['move-up', 'move-down', 'delete', 'open-form'],
    template: '<div class="shell-stub"><slot /></div>',
  },
  RCWidthPicker: {
    props: ['modelValue', 'allowedWidths'],
    emits: ['update:modelValue'],
    template: '<div class="width-picker-stub" />',
  },
  RCPresentationPicker: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<div class="presentation-picker-stub" />',
  },
};

function makeContent(options: Record<string, unknown> = {}): ContentPart {
  return { type: 'hero', json_content: {}, options: { variant: 'split', ...options } };
}

function mountToolbar(content: ContentPart) {
  return mount(HeroBlockToolbar, {
    props: {
      content,
      blockKey: 'hero-1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
    },
    global: { stubs },
  });
}

describe('HeroBlockToolbar', () => {
  it('renders one button per hero variant', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.findAll('button')).toHaveLength(4);
  });

  it('clicking a variant button emits update:content with that variant', async () => {
    const wrapper = mountToolbar(makeContent({ variant: 'split' }));
    // Buttons render in order: split, centered, banner, panel.
    await wrapper.findAll('button')[2]!.trigger('click');
    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as ContentPart).options?.variant).toBe('banner');
  });

  it('shows the presentation picker for split/centered/banner but hides it for panel', () => {
    expect(mountToolbar(makeContent({ variant: 'split' })).find('.presentation-picker-stub').exists()).toBe(true);
    expect(mountToolbar(makeContent({ variant: 'panel' })).find('.presentation-picker-stub').exists()).toBe(false);
  });

  it('shows the width picker', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.find('.width-picker-stub').exists()).toBe(true);
  });
});
