import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import emblaCarouselVue from 'embla-carousel-vue';

import Carousel from '../Carousel.vue';

import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

const api = vi.hoisted(() => ({
  on: vi.fn(),
  selectedScrollSnap: () => 1,
  scrollTo: vi.fn(),
  scrollNext: vi.fn(),
  scrollPrev: vi.fn(),
  canScrollNext: () => true,
  canScrollPrev: () => true,
}));

vi.mock('embla-carousel-vue', async () => {
  const { shallowRef } = await import('vue');
  return { default: vi.fn(() => [shallowRef(), shallowRef(api)]) };
});

const preferences = useAccessibilityPreferences();
let wrapper: ReturnType<typeof mount>;

afterEach(() => {
  wrapper?.unmount();
  preferences.reset();
  vi.clearAllMocks();
});

describe('Carousel motion', () => {
  it('makes keyboard movement immediate and settles an active slide when motion is reduced', async () => {
    wrapper = mount(Carousel, { props: { opts: { duration: 32 } } });
    await wrapper.trigger('keydown', { key: 'ArrowRight' });
    expect(api.scrollNext).toHaveBeenLastCalledWith(false);

    preferences.reduceMotion.value = true;
    await nextTick();
    expect(api.scrollTo).toHaveBeenLastCalledWith(1, true);
    const options = vi.mocked(emblaCarouselVue).mock.calls.at(-1)![0];
    expect(options).toHaveProperty('value.duration', 0);
    await wrapper.trigger('keydown', { key: 'ArrowLeft' });
    expect(api.scrollPrev).toHaveBeenLastCalledWith(true);

    preferences.reduceMotion.value = false;
    await nextTick();
    expect(options).toHaveProperty('value.duration', 32);
  });

  it('forwards changed options and plugins reactively', async () => {
    wrapper = mount(Carousel, { props: { opts: { loop: false } } });
    const [options, plugins] = vi.mocked(emblaCarouselVue).mock.calls.at(-1)!;
    await wrapper.setProps({ opts: { loop: true }, orientation: 'vertical' });
    expect(options).toHaveProperty('value.loop', true);
    expect(options).toHaveProperty('value.axis', 'y');
    expect(plugins).toHaveProperty('value', []);
  });
});
