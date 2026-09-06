import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';

const darkRef = ref(false);
vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal() as Record<string, unknown>;
  return { ...actual, useDark: () => darkRef };
});

import RCSpotifyEmbed from '../RCSpotifyEmbed.vue';
import type { SpotifyEmbed } from '@/Types/contentParts';

function makeElement(overrides: Partial<SpotifyEmbed['json_content']> = {}, options: SpotifyEmbed['options'] = null): SpotifyEmbed {
  return { json_content: { url: '', ...overrides }, options };
}

describe('RCSpotifyEmbed', () => {
  it('renders the plain bordered iframe for the default inline variant', () => {
    const wrapper = mount(RCSpotifyEmbed, { props: { element: makeElement({ url: 'https://open.spotify.com/show/abc' }) } });

    expect(wrapper.find('iframe').attributes('src')).toBe('https://open.spotify.com/show/abc?theme=1');
    expect(wrapper.findComponent({ name: 'RCSpotifyPromoDisplay' }).exists()).toBe(false);
  });

  it('dispatches to RCMixcloudEmbed for a mixcloud URL when inline', () => {
    const wrapper = mount(RCSpotifyEmbed, { props: { element: makeElement({ url: 'https://www.mixcloud.com/startfm/episode/' }) } });

    expect(wrapper.findComponent({ name: 'RCMixcloudEmbed' }).exists()).toBe(true);
  });

  it('renders RCSpotifyPromoDisplay instead when options.variant is "promo"', () => {
    const wrapper = mount(RCSpotifyEmbed, {
      props: { element: makeElement({ url: 'https://open.spotify.com/show/abc' }, { variant: 'promo' }) },
      global: { stubs: { SmartLink: { props: ['href'], template: '<a :href="href"><slot /></a>' } } },
    });

    expect(wrapper.findComponent({ name: 'RCSpotifyPromoDisplay' }).exists()).toBe(true);
    expect(wrapper.find('.border').exists()).toBe(true); // still renders something, not blank
  });
});
