import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';

import { ssrRoundTrip } from '@/tests/helpers/ssrRoundTrip';

const tableDoc = {
  type: 'doc',
  content: [
    {
      type: 'table',
      content: [
        {
          type: 'tableRow',
          content: [
            { type: 'tableHeader', content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Header' }] }] },
          ],
        },
      ],
    },
  ],
};

const paragraphDoc = {
  type: 'doc',
  content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Just text' }] }],
};

describe('RichContentTiptapHTML', () => {
  it('wraps a rendered table in a scrollable .tableWrapper, so a wide table scrolls instead of overflowing the page', () => {
    const wrapper = mount(RichContentTiptapHTML, { props: { json_content: tableDoc } });

    const tableWrapper = wrapper.find('.tableWrapper');
    expect(tableWrapper.exists()).toBe(true);
    expect(tableWrapper.find('table').exists()).toBe(true);
  });

  it('renders non-table content without adding a .tableWrapper', () => {
    const wrapper = mount(RichContentTiptapHTML, { props: { json_content: paragraphDoc } });

    expect(wrapper.find('.tableWrapper').exists()).toBe(false);
    expect(wrapper.text()).toContain('Just text');
  });

  it('renders superscript and subscript marks the editor lets authors apply', () => {
    const wrapper = mount(RichContentTiptapHTML, {
      props: {
        json_content: {
          type: 'doc',
          content: [{
            type: 'paragraph',
            content: [
              { type: 'text', text: 'm' },
              { type: 'text', text: '2', marks: [{ type: 'superscript' }] },
              { type: 'text', text: 'H' },
              { type: 'text', text: '2', marks: [{ type: 'subscript' }] },
            ],
          }],
        },
      },
    });

    expect(wrapper.find('sup').text()).toBe('2');
    expect(wrapper.find('sub').text()).toBe('2');
  });

  it('server-renders the text, so the first paint is not empty', async () => {
    const { html, unmount } = await ssrRoundTrip(RichContentTiptapHTML, { json_content: tableDoc });

    expect(html).toContain('Header');
    expect(html).toContain('class="tableWrapper"');
    unmount();
  });

  it('hydrates the server markup without a mismatch', async () => {
    const { container, hydrationWarnings, unmount } = await ssrRoundTrip(RichContentTiptapHTML, { json_content: paragraphDoc });

    expect(hydrationWarnings).toEqual([]);
    expect(container.textContent).toContain('Just text');
    unmount();
  });
});
