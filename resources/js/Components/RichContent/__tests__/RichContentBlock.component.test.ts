import { describe, expect, it } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import RichContentBlock from '../RichContentBlock.vue';

// Warm up the async display so Suspense resolves inside the test environment.
import '../RichContentCard.vue';

const cardElement = {
  type: 'shadcn-card',
  json_content: {
    type: 'doc',
    content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Hello from card' }] }],
  },
  options: { title: 'Card title' },
} as unknown as models.ContentPart;

const tiptapElement = {
  type: 'tiptap',
  json_content: {
    type: 'doc',
    content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Live text' }] }],
  },
  options: {},
} as unknown as models.ContentPart;

const tiptapWithHtml = {
  type: 'tiptap',
  html: '<p>Server html</p>',
  json_content: {},
  options: {},
} as unknown as models.ContentPart;

async function mountBlock(element: models.ContentPart) {
  return mount(RichContentBlock, {
    props: { element },
    global: {
      stubs: {
        RichContentTiptapHTML: {
          props: ['json_content'],
          template: '<div class="tiptap-stub">{{ JSON.stringify(json_content) }}</div>',
        },
      },
    },
  });
}

describe('RichContentBlock', () => {
  it('renders shadcn-card body from json_content through the async display slot', async () => {
    const wrapper = await mountBlock(cardElement);
    await flushPromises();

    expect(wrapper.find('[data-slot="card-title"]').text()).toBe('Card title');
    expect(wrapper.find('.tiptap-stub').text()).toContain('Hello from card');
  });

  it('renders tiptap from json_content when element.html is undefined', async () => {
    const wrapper = await mountBlock(tiptapElement);
    await flushPromises();

    expect(wrapper.find('.tiptap-stub').text()).toContain('Live text');
  });

  it('uses server-rendered html for tiptap when available', async () => {
    const wrapper = mount(RichContentBlock, {
      props: { element: tiptapWithHtml },
    });
    await flushPromises();

    expect(wrapper.html()).toContain('Server html');
  });
});
