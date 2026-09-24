import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import NotificationCategoryTable from '../NotificationCategoryTable.vue';
import { commonStubs } from '@/tests/stubs';

const props = {
  categories: {
    comment: { value: 'comment', modelEnumKey: 'COMMENT', color: 'neutral' },
    task: { value: 'task', modelEnumKey: 'TASK', color: 'blue' },
  },
  channels: {
    in_app: { value: 'in_app', enabledByDefault: true },
    email_digest: { value: 'email_digest', enabledByDefault: true },
    push: { value: 'push', enabledByDefault: false },
  },
  formChannels: {
    comment: { in_app: true, email_digest: true, push: false },
    task: { in_app: true, email_digest: false, push: true },
  },
};

describe('NotificationCategoryTable', () => {
  it('renders all categories and channel columns', () => {
    const wrapper = mount(NotificationCategoryTable, {
      props,
      global: { stubs: commonStubs },
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows).toHaveLength(2);

    const headers = wrapper.findAll('thead th');
    // 1 category column + 3 channel columns
    expect(headers).toHaveLength(4);
  });

  it('emits update:channel when a checkbox is toggled', async () => {
    const wrapper = mount(NotificationCategoryTable, {
      props,
      global: { stubs: commonStubs },
    });

    // Checkboxes are rendered in td elements
    const checkboxes = wrapper.findAllComponents({ name: 'Checkbox' });
    expect(checkboxes.length).toBe(6);

    // Toggle the first checkbox (comment -> in_app)
    await checkboxes[0].vm.$emit('update:modelValue', false);

    expect(wrapper.emitted('update:channel')).toBeTruthy();
    expect(wrapper.emitted('update:channel')![0]).toEqual(['comment', 'in_app', false]);
  });
});
