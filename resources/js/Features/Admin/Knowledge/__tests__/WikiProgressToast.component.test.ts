import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import WikiProgressToast from '../WikiProgressToast.vue';

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

it('leads with the Wiki home meter, counting up from where you were, and names the article below it', async () => {
  const wrapper = mount(WikiProgressToast, { props: { progress: { title: 'Kvorumas', done: 4, total: 8, previousDone: 3 } } });
  const count = () => wrapper.get('[data-slot="wiki-progress-count"]').text();
  const width = () => wrapper.get<HTMLElement>('[data-slot="wiki-progress-bar"]').element.style.width;
  expect(count()).toBe('3/8');
  expect(width()).toBe('38%');
  expect(wrapper.html().indexOf('wiki-progress-count')).toBeLessThan(wrapper.html().indexOf('Kvorumas'));
  // The meter's label stays for screen readers only.
  expect(wrapper.get('dt').classes()).toContain('sr-only');

  await vi.advanceTimersByTimeAsync(1000);
  await nextTick();
  expect(count()).toBe('4/8');
  expect(width()).toBe('50%');
  expect(wrapper.text()).not.toContain('learning.wiki.toast_complete');
});

it('says so when the whole Wiki is learned', () => {
  const wrapper = mount(WikiProgressToast, { props: { progress: { title: 'Kvorumas', done: 8, total: 8, previousDone: 7 } } });
  expect(wrapper.text()).toContain('learning.wiki.toast_complete');
});
