import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

import DarkModeButton from '@/Components/Buttons/DarkModeButton.vue';

beforeEach(async () => {
  localStorage.setItem('vueuse-color-scheme', 'light');
  document.documentElement.classList.remove('dark');
  document.head.innerHTML = '<meta id="theme-color" name="theme-color" content="#ffffff">';
  await nextTick();
});

afterEach(() => {
  localStorage.removeItem('vueuse-color-scheme');
  document.documentElement.classList.remove('dark');
  document.head.innerHTML = '';
});

describe('DarkModeButton.vue', () => {
  it('applies the page theme and browser chrome colour during the click', () => {
    const wrapper = mount(DarkModeButton);

    wrapper.get('button').element.click();

    expect(document.documentElement.classList.contains('dark')).toBe(true);
    expect(document.querySelector('meta[name="theme-color"]')?.getAttribute('content'))
      .toBe('#252528');
  });
});
