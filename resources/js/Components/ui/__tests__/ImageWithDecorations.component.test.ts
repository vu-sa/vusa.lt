import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import ImageWithDecorations from '../ImageWithDecorations.vue';

describe('ImageWithDecorations', () => {
  it('does not render the overlay card when overlayContent has no title or subtitle', () => {
    const wrapper = mount(ImageWithDecorations, {
      props: { src: '/a.jpg', alt: 'A', overlayContent: { title: '', subtitle: '' } },
    });
    expect(wrapper.text()).not.toContain('undefined');
    // The overlay card is the only element carrying overlayPosition/overlayStyle
    // classes; assert on its distinguishing shadow-xl class instead of text content,
    // since empty title/subtitle would leave no text either way.
    expect(wrapper.find('.shadow-xl').exists()).toBe(false);
  });

  it('renders the overlay card when a title or subtitle is set', () => {
    const wrapper = mount(ImageWithDecorations, {
      props: { src: '/a.jpg', alt: 'A', overlayContent: { title: '1500+', subtitle: 'aktyvių narių' } },
    });
    expect(wrapper.find('.shadow-xl').exists()).toBe(true);
    expect(wrapper.text()).toContain('1500+');
    expect(wrapper.text()).toContain('aktyvių narių');
  });

  it('renders the overlay card when only a subtitle is set', () => {
    const wrapper = mount(ImageWithDecorations, {
      props: { src: '/a.jpg', alt: 'A', overlayContent: { title: '', subtitle: 'Tik paantraštė' } },
    });
    expect(wrapper.find('.shadow-xl').exists()).toBe(true);
  });

  it('renders no overlay card when overlayContent is not passed at all', () => {
    const wrapper = mount(ImageWithDecorations, { props: { src: '/a.jpg', alt: 'A' } });
    expect(wrapper.find('.shadow-xl').exists()).toBe(false);
  });

  it('can force an empty overlay card and lets an editor replace its text through the overlay slot', () => {
    const wrapper = mount(ImageWithDecorations, {
      props: { src: '/a.jpg', alt: 'A', forceOverlayContent: true },
      slots: { 'overlay-content': '<span class="editable-overlay">Editable overlay</span>' },
    });

    expect(wrapper.find('.shadow-xl').exists()).toBe(true);
    expect(wrapper.get('.editable-overlay').text()).toBe('Editable overlay');
  });
});
