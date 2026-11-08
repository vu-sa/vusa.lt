import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import type { SpotifyEmbed } from '@/Types/contentParts';

import RCMixcloudEmbed from '../RCMixcloudEmbed.vue';

describe('Mixcloud player', () => {
  it.each(['https://example.org/player', 'http://www.mixcloud.com/startfm/episode/'])('omits an unsupported player at %s', (url) => {
    const element: SpotifyEmbed = { json_content: { url }, options: null };
    const wrapper = mount(RCMixcloudEmbed, { props: { element } });
    expect(wrapper.find('iframe').exists()).toBe(false);
  });
});
