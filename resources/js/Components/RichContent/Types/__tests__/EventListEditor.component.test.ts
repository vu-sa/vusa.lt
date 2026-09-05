import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import EventListEditor from '../EventListEditor.vue';
import type { EventList } from '@/Types/contentParts';
import { createContentItem } from '../index';

function makeItem(): { json_content: EventList['json_content']; options: EventList['options'] } {
  const item = createContentItem('event-list');
  return { json_content: item.json_content, options: item.options };
}

describe('EventListEditor', () => {
  it('mounts without throwing and shows the range date fields when mode=range', async () => {
    const item = makeItem();
    item.options.mode = 'range';
    const wrapper = mount(EventListEditor, { props: { modelValue: item.json_content, options: item.options } });

    expect(wrapper.findAll('input[type="date"]')).toHaveLength(2);
  });

  it('shows the year field when mode=year', () => {
    const item = makeItem();
    item.options.mode = 'year';
    const wrapper = mount(EventListEditor, { props: { modelValue: item.json_content, options: item.options } });

    expect(wrapper.findAll('input[type="date"]')).toHaveLength(0);
  });

  it('toggling groupBy to tenant reveals the tenant label prefix field', async () => {
    const item = makeItem();
    const wrapper = mount(EventListEditor, { props: { modelValue: item.json_content, options: item.options } });

    const tenantGroupButton = wrapper.findAll('button').find(b => b.text().includes('group_by_tenant'));
    await tenantGroupButton!.trigger('click');

    const emitted = wrapper.emitted('update:options');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as EventList['options']).groupBy).toBe('tenant');
    expect(wrapper.text()).toContain('tenant_label_prefix');
  });
});
