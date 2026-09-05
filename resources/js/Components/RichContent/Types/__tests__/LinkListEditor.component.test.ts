import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import LinkListEditor from '../LinkListEditor.vue';
import type { LinkList } from '@/Types/contentParts';
import { createContentItem } from '../index';

const stubs = {
  CollectionSelectDialog: { template: '<div />' },
};

function makeItem(): { json_content: LinkList['json_content']; options: LinkList['options'] } {
  const item = createContentItem('link-list');
  return { json_content: item.json_content, options: item.options };
}

describe('LinkListEditor', () => {
  it('manual mode adds a link via DynamicListInput', async () => {
    const item = makeItem();
    item.options.source = 'manual';
    const wrapper = mount(LinkListEditor, {
      props: { modelValue: item.json_content, options: item.options },
      global: { stubs },
    });

    const addButton = wrapper.findAll('button').find(b => b.text().includes('add_first_link'));
    expect(addButton).toBeTruthy();
    await addButton!.trigger('click');

    const emitted = wrapper.emitted('update:modelValue');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as LinkList['json_content']).links).toHaveLength(1);
  });

  it('news/pages sourcing hides the manual link list', () => {
    const item = makeItem();
    item.options.source = 'news';
    const wrapper = mount(LinkListEditor, {
      props: { modelValue: item.json_content, options: item.options },
      global: { stubs },
    });

    expect(wrapper.text()).not.toContain('no_links');
  });

  it('toggling tenant scope to "all" updates options.tenantScope', async () => {
    const item = makeItem();
    item.options.source = 'news';
    item.options.mode = 'latest';
    const wrapper = mount(LinkListEditor, {
      props: { modelValue: item.json_content, options: item.options },
      global: { stubs },
    });

    const allButton = wrapper.findAll('button').find(b => b.text() === 'rich-content.tenant_scope_all' || b.text().includes('tenant_scope_all'));
    expect(allButton).toBeTruthy();
    await allButton!.trigger('click');

    const emitted = wrapper.emitted('update:options');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as LinkList['options']).tenantScope).toBe('all');
  });
});
