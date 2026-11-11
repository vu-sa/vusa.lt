import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import WikiProgressToast from '../WikiProgressToast.vue';

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

it('grows the bar and counts up from the previous to the new whole-Wiki progress', async () => {
  const wrapper = mount(WikiProgressToast, { props: { progress: { title: 'Kvorumas', done: 4, total: 8, previousDone: 3 } } });
  expect(wrapper.text()).toContain('Kvorumas');
  expect(wrapper.get('[data-slot="wiki-progress-toast-count"]').text()).toBe('3/8');
  expect(wrapper.get<HTMLElement>('[data-slot="wiki-progress-toast-bar"]').element.style.width).toBe('37.5%');

  await vi.advanceTimersByTimeAsync(1000);
  await nextTick();
  expect(wrapper.get('[data-slot="wiki-progress-toast-count"]').text()).toBe('4/8');
  expect(wrapper.get<HTMLElement>('[data-slot="wiki-progress-toast-bar"]').element.style.width).toBe('50%');
  expect(wrapper.text()).not.toContain('learning.wiki.toast_complete');
});

it('says so when the whole Wiki is learned', () => {
  const wrapper = mount(WikiProgressToast, { props: { progress: { title: 'Kvorumas', done: 8, total: 8, previousDone: 7 } } });
  expect(wrapper.text()).toContain('learning.wiki.toast_complete');
});
