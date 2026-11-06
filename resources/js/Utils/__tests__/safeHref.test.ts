import { describe, expect, it } from 'vitest';

import { safeHref } from '../safeHref';

describe('safeHref', () => {
  it.each([
    'https://example.com/path', 'HTTP://example.com', '/lt/kontaktai', '#anchor',
    'kontaktai', '../kontaktai', '?page=2', '//example.com/path',
    'mailto:info@example.com', 'tel:+37012345678',
  ])('preserves supported URL %s', (href) => {
    expect(safeHref(href)).toBe(href);
  });

  it.each([
    'javascript:void(0)', 'data:text/plain,hello', 'vbscript:MsgBox(1)',
    ' \u0000JaVa\tsCrIpT:void(0)\r\n', 'java\nscript:void(0)', 'ftp://example.com',
    '', null, undefined, 123, {},
  ])('omits unsupported URL %s', (href) => {
    expect(safeHref(href)).toBeUndefined();
  });

  it('trims surrounding whitespace', () => {
    expect(safeHref(' \thttps://example.com\r\n')).toBe('https://example.com');
  });
});
