import { nextTick, type Ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

let intersectionCallback: ((entries: { isIntersecting: boolean }[]) => void) | undefined;

vi.mock('@vueuse/core', () => ({
  TransitionPresets: { easeOutCubic: undefined },
  useIntersectionObserver: (_target: unknown, callback: (entries: { isIntersecting: boolean }[]) => void) => {
    intersectionCallback = callback;
    return { stop: vi.fn() };
  },
  useTransition: (source: Ref<number>) => source,
}));

import RCNumberCounter from '../RCNumberCounter.vue';

describe('RCNumberCounter', () => {
  it('updates an already-visible figure when its value changes', async () => {
    const wrapper = mount(RCNumberCounter, { props: { endNumber: 5 } });

    intersectionCallback?.([{ isIntersecting: true }]);
    await nextTick();
    await wrapper.setProps({ endNumber: 42 });

    expect(wrapper.text()).toBe('42');
  });
});
