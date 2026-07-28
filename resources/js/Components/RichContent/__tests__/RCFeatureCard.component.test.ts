import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCFeatureCard from '../RCFeatureCard.vue';

describe('RCFeatureCard', () => {
  it('renders the fallback cover box when there is no image (default)', () => {
    const wrapper = mount(RCFeatureCard, { props: { title: 'Kortelė' } });
    expect(wrapper.find('.aspect-\\[16\\/9\\]').exists()).toBe(true);
  });

  it('omits the cover box entirely when showCoverFallback is false and there is no image', () => {
    const wrapper = mount(RCFeatureCard, { props: { title: 'Kortelė', showCoverFallback: false } });
    expect(wrapper.find('.aspect-\\[16\\/9\\]').exists()).toBe(false);
    expect(wrapper.text()).toContain('Kortelė');
  });

  it('still renders the cover box when an image is set, even with showCoverFallback false', () => {
    const wrapper = mount(RCFeatureCard, {
      props: { title: 'Kortelė', coverImage: '/a.jpg', showCoverFallback: false },
    });
    expect(wrapper.find('img[src="/a.jpg"]').exists()).toBe(true);
  });
});
