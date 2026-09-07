import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';

describe('RichContentTiptapHTML', () => {
  it('wraps a rendered table in a scrollable .tableWrapper, so a wide table scrolls instead of overflowing the page', () => {
    const wrapper = mount(RichContentTiptapHTML, {
      props: {
        json_content: {
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
        },
      },
    });

    const tableWrapper = wrapper.find('.tableWrapper');
    expect(tableWrapper.exists()).toBe(true);
    expect(tableWrapper.find('table').exists()).toBe(true);
  });

  it('renders non-table content without adding a .tableWrapper', () => {
    const wrapper = mount(RichContentTiptapHTML, {
      props: {
        json_content: {
          type: 'doc',
          content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Just text' }] }],
        },
      },
    });

    expect(wrapper.find('.tableWrapper').exists()).toBe(false);
    expect(wrapper.text()).toContain('Just text');
  });
});
