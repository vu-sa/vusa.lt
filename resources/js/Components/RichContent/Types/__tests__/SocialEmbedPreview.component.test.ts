import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SocialEmbedPreview from '../SocialEmbedPreview.vue';

describe('social preview', () => {
  it('omits provider markup when the URL does not match the selected platform', () => {
    const wrapper = mount(SocialEmbedPreview, {
      props: { platform: 'facebook', url: 'https://example.org/posts/123' },
    });
    expect(wrapper.find('.fb-post, .instagram-media, iframe, a').exists()).toBe(false);
  });
});
