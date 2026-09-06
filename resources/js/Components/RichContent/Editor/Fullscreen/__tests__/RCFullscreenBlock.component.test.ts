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
  NewsBlockToolbar: { template: '<div class="news-toolbar" />' },
  CardBlockToolbar: { template: '<div class="card-toolbar" />' },
  NumberStatBlockToolbar: { template: '<div class="number-stat-toolbar" />' },
  FlowGraphBlockToolbar: { template: '<div class="flow-graph-toolbar" />' },
  CardStackBlockToolbar: { template: '<div class="card-stack-toolbar" />' },
  CarouselSlideDeckBlockToolbar: { template: '<div class="carousel-slide-deck-toolbar" />' },
  ProcessStepsBlockToolbar: { template: '<div class="process-steps-toolbar" />' },
  RCImageListBlockToolbar: { template: '<div class="image-list-toolbar" />' },
  RCBlockToolbarShell: { template: '<div class="block-toolbar"><slot /></div>' },
  RCSectionToolbarOptions: {
    props: ['presentationDisabled'],
    template: '<div class="section-toolbar-options"><div class="presentation-picker" :data-disabled="presentationDisabled" /></div>',
  },
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
    ['news', '.news-toolbar'],
    ['shadcn-card', '.card-toolbar'],
    ['number-stat-section', '.number-stat-toolbar'],
    ['flow-graph', '.flow-graph-toolbar'],
    ['image-grid', '.image-list-toolbar'],
    ['photo-gallery', '.image-list-toolbar'],
    ['card-stack', '.card-stack-toolbar'],
    ['carousel-slide-deck', '.carousel-slide-deck-toolbar'],
    ['process-steps', '.process-steps-toolbar'],
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
