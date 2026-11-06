import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import RCSocialEmbed from '../RCSocialEmbed.vue';

import type { SocialEmbed } from '@/Types/contentParts';

describe('social embed', () => {
  it.each(['https://example.org/post/1', 'http://www.facebook.com/posts/123', 'data:text/html,example'])('omits an unsupported source at %s', (url) => {
    const element: SocialEmbed = { json_content: { url, platform: null }, options: {} };
    const wrapper = mount(RCSocialEmbed, { props: { element } });
    expect(wrapper.find('iframe, a, .fb-post, .instagram-media').exists()).toBe(false);
  });
});
