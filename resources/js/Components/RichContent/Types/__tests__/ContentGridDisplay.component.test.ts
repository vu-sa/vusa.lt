import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import ContentGridDisplay from '../ContentGridDisplay.vue';
import DecorativeElement from '@/Components/ui/DecorativeElement.vue';

describe('ContentGridDisplay', () => {
  it('renders a card cell through RCFeatureCard', () => {
    const element = {
      json_content: [
        {
          columns: [
            {
              width: 'col-span-7',
              content: { type: 'tiptap', value: { type: 'doc', content: [] } },
            },
            {
              width: 'col-span-5',
              content: { type: 'card', value: { image: '/x.jpg', imageAlt: 'X', title: 'Kortelė', description: 'Aprašymas', href: '#' } },
            },
          ],
        },
      ],
      options: { gap: 'gap-4', mobileStacking: true, equalHeight: false },
    };

    const wrapper = mount(ContentGridDisplay, {
      props: { element },
      global: { stubs: { SmartLink: { props: ['href'], template: '<a :href="href"><slot /></a>' } } },
    });

    expect(wrapper.text()).toContain('Kortelė');
    expect(wrapper.text()).toContain('Aprašymas');
  });

  it('still supports the legacy tiptap/image cell types', () => {
    const element = {
      json_content: [
        {
          columns: [
            { width: 'col-span-6', content: { type: 'image', value: '/a.jpg', alt: 'A photo' } },
          ],
        },
      ],
      options: { gap: 'gap-4', mobileStacking: true, equalHeight: false },
    };

    const wrapper = mount(ContentGridDisplay, { props: { element } });
    expect(wrapper.find('img[src="/a.jpg"]').exists()).toBe(true);
  });

  it('has no background/padding by default, so existing grids look unchanged', () => {
    const element = {
      json_content: [{ columns: [{ width: 'col-span-12', content: { type: 'tiptap', value: {} } }] }],
      options: { gap: 'gap-4', mobileStacking: true, equalHeight: false },
    };
    const wrapper = mount(ContentGridDisplay, { props: { element } });
    expect(wrapper.find('section').classes().join(' ')).not.toMatch(/py-|bg-/);
  });

  it('renders the gradient background and a title when opted in', () => {
    const element = {
      json_content: [{ columns: [{ width: 'col-span-12', content: { type: 'tiptap', value: {} } }] }],
      options: { gap: 'gap-4', mobileStacking: true, equalHeight: false, background: 'gradient' as const, padding: 'md' as const, title: 'Apie mus' },
    };
    const wrapper = mount(ContentGridDisplay, { props: { element } });
    expect(wrapper.find('section').classes().join(' ')).toContain('bg-gradient-to-br');
    expect(wrapper.text()).toContain('Apie mus');
  });

  it('passes decorations through to ImageWithDecorations', () => {
    const element = {
      json_content: [
        {
          columns: [
            {
              width: 'col-span-6',
              content: {
                type: 'image',
                value: '/a.jpg',
                alt: 'A',
                decorations: [{ type: 'line', position: 'top-right', size: 'lg', color: 'vusa-yellow', opacity: 60 }],
              },
            },
          ],
        },
      ],
      options: { gap: 'gap-4', mobileStacking: true, equalHeight: false },
    };
    const wrapper = mount(ContentGridDisplay, { props: { element } });
    expect(wrapper.findComponent(DecorativeElement).exists()).toBe(true);
  });
});
