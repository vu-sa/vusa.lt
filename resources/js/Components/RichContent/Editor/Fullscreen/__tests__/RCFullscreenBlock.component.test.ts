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
  RCBlockToolbarShell: { template: '<div class="block-toolbar" />' },
  RCWidthPicker: { template: '<div />' },
  RCPresentationPicker: { template: '<div />' },
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
});
