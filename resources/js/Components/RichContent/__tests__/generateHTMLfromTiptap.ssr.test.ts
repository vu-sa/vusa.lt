// @vitest-environment node
import { describe, expect, it } from 'vitest';

import { generateHTMLfromTiptap } from '../RichContentTiptapHTML.vue';

// The Inertia SSR server has no DOM; rendering here must not reach for one.
describe('generateHTMLfromTiptap without a DOM', () => {
  it('renders text and the table scroll wrapper', () => {
    expect(typeof document).toBe('undefined');

    const html = generateHTMLfromTiptap({
      type: 'doc',
      content: [
        { type: 'paragraph', content: [{ type: 'text', text: 'Server text' }] },
        {
          type: 'table',
          content: [{
            type: 'tableRow',
            content: [{ type: 'tableCell', content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Cell' }] }] }],
          }],
        },
      ],
    });

    expect(html).toContain('<p>Server text</p>');
    expect(html).toMatch(/<div class="tableWrapper"><table class="rc-table"[^>]*>.*Cell.*<\/table><\/div>/);
  });
});
