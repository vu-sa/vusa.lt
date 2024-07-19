import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import AdminContentPage from './AdminContentPage.vue';

describe('AdminContentPage', () => {
  // https://runthatline.com/how-to-mock-window-with-vitest/
  it('mounts', () => {
    const wrapper = mount(AdminContentPage);

    expect(wrapper.exists()).toBe(true);
  });
});

