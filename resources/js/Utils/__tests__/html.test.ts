import { describe, expect, it } from 'vitest';

import { stripHtml } from '../html';

describe('HTML text conversion', () => {
  it('keeps readable text and decodes entities', () => {
    expect(stripHtml('<p>Hello <strong>world</strong> &amp; friends</p>')).toBe('Hello world & friends');
  });

  it('omits script and style contents', () => {
    expect(stripHtml('<style>p { color: red }</style><script>void(0)</script><p>Text</p>')).toBe('Text');
  });

  it('returns no text for an image without a caption', () => {
    expect(stripHtml('<img src="/photo.png" onload="void(0)">')).toBe('');
    expect(stripHtml(null)).toBe('');
    expect(stripHtml()).toBe('');
  });
});
