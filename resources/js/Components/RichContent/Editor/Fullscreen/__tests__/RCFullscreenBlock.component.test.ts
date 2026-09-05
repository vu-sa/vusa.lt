import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCFullscreenBlock from '../RCFullscreenBlock.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../useActiveHotspot';

import type { ContentPart } from '@/Components/RichContent/Types';

const stubs = {
  BlockPreviewRenderer: {
    props: ['preview'],
    template: '<div class="block-preview" :data-preview="preview" />',
  },
  HeroBlockToolbar: { template: '<div class="hero-toolbar" />' },
  LinkListBlockToolbar: { template: '<div class="link-list-toolbar" />' },
  EventListBlockToolbar: { template: '<div class="event-list-toolbar" />' },
  CalendarBlockToolbar: { template: '<div class="calendar-toolbar" />' },
  RCBlockToolbarShell: { template: '<div class="block-toolbar"><slot /></div>' },
  RCWidthPicker: { template: '<div />' },
  RCPresentationPicker: {
    props: ['disabled'],
    template: '<div class="presentation-picker" :data-disabled="disabled" />',
  },
};

function mountBlock(preview: boolean) {
  return mount(RCFullscreenBlock, {
    props: {
      content: { type: 'hero', json_content: {}, options: {} } as ContentPart,
      blockKey: 'hero-1',
      canMoveUp: false,
      canMoveDown: false,
      canDelete: false,
      preview,
    },
    global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: useActiveHotspot() } },
  });
}

describe('RCFullscreenBlock', () => {
  it('renders the published block without editing chrome in preview mode', () => {
    const wrapper = mountBlock(true);

    expect(wrapper.get('.block-preview').attributes('data-preview')).toBe('true');
    expect(wrapper.find('.hero-toolbar').exists()).toBe(false);
    expect(wrapper.find('.block-toolbar').exists()).toBe(false);
  });

  it('disables a wrapped grid presentation controls', () => {
    const wrapper = mount(RCFullscreenBlock, {
      props: {
        content: { type: 'content-grid', json_content: {}, options: {} } as ContentPart,
        band: { isBand: false, tint: null, bleeds: false, classes: [], isSectionChild: true },
        blockKey: 'grid-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: useActiveHotspot() } },
    });

    expect(wrapper.get('.presentation-picker').attributes('data-disabled')).toBe('true');
  });

  it.each([
    ['link-list', '.link-list-toolbar'],
    ['event-list', '.event-list-toolbar'],
    ['calendar', '.calendar-toolbar'],
  ])('routes %s to its dedicated toolbar, not the generic fallback', (type, toolbarSelector) => {
    const wrapper = mount(RCFullscreenBlock, {
      props: {
        content: { type, json_content: {}, options: {} } as ContentPart,
        blockKey: `${type}-1`,
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: useActiveHotspot() } },
    });

    expect(wrapper.find(toolbarSelector).exists()).toBe(true);
    expect(wrapper.find('.block-toolbar').exists()).toBe(false);
  });
});
