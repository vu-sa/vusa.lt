import { nextTick, type Ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { useTransition } from '@vueuse/core';

let intersectionCallback: ((entries: { isIntersecting: boolean }[]) => void) | undefined;

vi.mock('@vueuse/core', async importOriginal => ({
  ...await importOriginal<typeof import('@vueuse/core')>(),
  TransitionPresets: { easeOutCubic: undefined },
  useIntersectionObserver: (_target: unknown, callback: (entries: { isIntersecting: boolean }[]) => void) => {
    intersectionCallback = callback;
    return { stop: vi.fn() };
  },
  useTransition: vi.fn((source: Ref<number>) => source),
}));

import RCNumberCounter from '../RCNumberCounter.vue';

import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

describe('RCNumberCounter', () => {
  it('disables interpolation when the motion preference changes', async () => {
    const preferences = useAccessibilityPreferences();
    preferences.reset();
    const wrapper = mount(RCNumberCounter, { props: { endNumber: 42 } });
    const options = vi.mocked(useTransition).mock.calls.at(-1)![1];
    expect(options).toHaveProperty('disabled.value', false);
    preferences.reduceMotion.value = true;
    await nextTick();
    expect(options).toHaveProperty('disabled.value', true);
    expect(wrapper.text()).toBe('42');
    wrapper.unmount();
    preferences.reset();
  });

  it('updates an already-visible figure when its value changes', async () => {
    const wrapper = mount(RCNumberCounter, { props: { endNumber: 5 } });

    intersectionCallback?.([{ isIntersecting: true }]);
    await nextTick();
    await wrapper.setProps({ endNumber: 42 });

    expect(wrapper.text()).toBe('42');
  });
});
