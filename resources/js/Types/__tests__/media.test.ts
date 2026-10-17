import { describe, expect, it } from 'vitest';

import { cmsSrcset } from '../media';

describe('cmsSrcset', () => {
  it('offers resized copies of a file-manager image, keeping the path encoded', () => {
    expect(cmsSrcset('/uploads/files/padaliniai/My photo.jpg')).toBe([400, 900, 1600, 2400, 3840]
      .map(width => `/img/${width}/files/padaliniai/My%20photo.jpg ${width}w`).join(', '));
  });

  it('accepts already encoded and absolute vusa.lt URLs', () => {
    expect(cmsSrcset('https://www.vusa.lt/uploads/files/My%20photo.png')).toContain('/img/400/files/My%20photo.png 400w');
  });

  it.each([
    ['media-library files', '/uploads/media/12/photo.webp'],
    ['foreign hosts', 'https://evil.test/uploads/files/photo.jpg'],
    ['non-image files', '/uploads/files/report.pdf'],
    ['other paths', '/images/logo.png'],
    ['empty values', null],
  ])('skips %s', (_label, src) => {
    expect(cmsSrcset(src)).toBeNull();
  });
});
