import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import TiptapEditable from '../TiptapEditable.vue';

describe('TiptapEditable', () => {
  it('opens a new, empty block for editing instead of rejecting it as unsupported', async () => {
    const wrapper = mount(TiptapEditable, {
      props: { element: { type: 'tiptap', json_content: {} }, editable: true },
      global: { stubs: { RCSmartTiptapToolbar: true, TiptapContextMenus: true } },
    });
    await flushPromises();

    expect(wrapper.find('[role=alert]').exists()).toBe(false);
    expect(wrapper.find('[contenteditable=true]').exists()).toBe(true);
  });
});
