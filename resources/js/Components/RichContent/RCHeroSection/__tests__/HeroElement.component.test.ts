import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroElement from '../HeroElement.vue';
import type { Hero } from '@/Types/contentParts';

function makeElement(overrides: Partial<Hero['options']> = {}, jsonOverrides: Partial<Hero['json_content']> = {}): Hero {
  return {
    json_content: {
      title: 'Prisijunk',
      description: 'Aprašymas',
      imageSrc: '/img.jpg',
      imageAlt: 'Alt',
      buttons: [{ text: 'Registruotis', link: '#' }],
      ...jsonOverrides,
    },
    options: { textLeft: true, ...overrides },
  };
}

const stubs = {
  ImageWithDecorations: { template: '<div class="image-with-decorations" />' },
  SmartLink: { template: '<a><slot /></a>' },
};

describe('HeroElement', () => {
  it('defaults to the split variant when options.variant is unset', () => {
    const wrapper = mount(HeroElement, { props: { element: makeElement(), isFirstElement: true }, global: { stubs } });
    expect(wrapper.find('.image-with-decorations').exists()).toBe(true);
    expect(wrapper.find('h1').exists()).toBe(true);
  });

  it('centered variant renders no image', () => {
    const wrapper = mount(HeroElement, {
      props: { element: makeElement({ variant: 'centered' }), isFirstElement: true },
      global: { stubs },
    });
    expect(wrapper.find('.image-with-decorations').exists()).toBe(false);
    expect(wrapper.find('h1').exists()).toBe(true);
  });

  it('banner variant renders only the first button', () => {
    const wrapper = mount(HeroElement, {
      props: {
        element: makeElement({ variant: 'banner' }, { buttons: [{ text: 'A', link: '#a' }, { text: 'B', link: '#b' }] }),
        isFirstElement: true,
      },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('A');
    expect(wrapper.text()).not.toContain('B');
  });

  it('panel variant shows the eyebrow and a plain thumbnail image (no decorations)', () => {
    const wrapper = mount(HeroElement, {
      props: {
        element: makeElement({ variant: 'panel' }, { eyebrow: 'VU SA organizuoja' }),
        isFirstElement: true,
      },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('VU SA organizuoja');
    expect(wrapper.find('.image-with-decorations').exists()).toBe(false);
    expect(wrapper.find('img').exists()).toBe(true);
  });

  it('applies the anchor id from anchorId for ToC scroll targets', () => {
    const wrapper = mount(HeroElement, {
      props: { element: makeElement(), isFirstElement: true, anchorId: 42 },
      global: { stubs },
    });
    expect(wrapper.find('#rc-42').exists()).toBe(true);
  });
});
