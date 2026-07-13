import { describe, expect, it } from 'vitest';

import { stripHtml } from '../html';

describe('stripHtml', () => {
  it('returns plain text without tags', () => {
    expect(stripHtml('<p>Hello <strong>world</strong></p>')).toBe('Hello world');
  });

  it('returns empty string for undefined or null', () => {
    expect(stripHtml()).toBe('');
    expect(stripHtml(null)).toBe('');
  });

  it('decodes HTML entities', () => {
    expect(stripHtml('&amp;')).toBe('&');
  });
});
