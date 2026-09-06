import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import TextBoxBlockToolbar from '../TextBoxBlockToolbar.vue';
import type { ContentPart } from '../../Types';

function makeContent(overrides: Partial<ContentPart> = {}): ContentPart {
  return {
    type: 'text-box',
    json_content: {},
    options: {
      title: 'Feedback',
      placeholder: 'Write your thoughts...',
      isClosed: false,
      closedMessage: 'Closed',
    },
    ...overrides,
  };
}

describe('TextBoxBlockToolbar', () => {
  it('renders placeholder input', () => {
    const wrapper = mount(TextBoxBlockToolbar, {
      props: {
        content: makeContent(),
        blockKey: 'tb-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: {
        stubs: {
          RCBlockToolbarShell: { template: '<div><slot /></div>' },
          RCWidthPicker: true,
        },
      },
    });

    expect(wrapper.text()).toContain('rich-content.text_box_closed_label');
    expect(wrapper.text()).toContain('rich-content.text_box_placeholder_label');
  });

  it('shows closed message input only when isClosed is true', async () => {
    const wrapper = mount(TextBoxBlockToolbar, {
      props: {
        content: makeContent({ options: { isClosed: true, closedMessage: 'Submissions are closed' } }),
        blockKey: 'tb-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: {
        stubs: {
          RCBlockToolbarShell: { template: '<div><slot /></div>' },
          RCWidthPicker: true,
        },
      },
    });

    expect(wrapper.text()).toContain('rich-content.text_box_closed_message_label');
  });
});
