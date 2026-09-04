import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import { initThemeColor } from '../useThemeColor';

beforeEach(() => {
  document.documentElement.className = '';
  document.head.innerHTML = '<meta name="theme-color" content="#ffffff">';
});

afterEach(() => {
  document.head.innerHTML = '';
});

describe('initThemeColor', () => {
  it('keeps the browser chrome colour in sync with the app theme', async () => {
    initThemeColor();

    document.documentElement.classList.add('dark');
    await Promise.resolve();

    expect(document.querySelector('meta[name="theme-color"]')?.getAttribute('content'))
      .toBe('#252528');

    document.documentElement.classList.remove('dark');
    await Promise.resolve();

    expect(document.querySelector('meta[name="theme-color"]')?.getAttribute('content'))
      .toBe('#ffffff');
  });
});
