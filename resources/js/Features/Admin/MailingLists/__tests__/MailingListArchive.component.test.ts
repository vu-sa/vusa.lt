import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import MailingListArchive from '@/Features/Admin/MailingLists/MailingListArchive.vue';

it('expands plain-text posts without rendering stored markup or unsafe links', async () => {
  const wrapper = mount(MailingListArchive, {
    props: { messages: [{
      id: 1, subject: 'Meeting', sender_name: 'Coordinator', sender_email: 'rep@vusa.lt', sent_at: null,
      body_text: '<img src=x onerror=alert(1)>', url: 'javascript:alert(1)',
      attachments: [{ name: 'notes.pdf', size: 1, url: 'https://konf.vusa.lt/notes.pdf' }],
    }] },
  });
  expect(wrapper.find('button').attributes('aria-expanded')).toBe('false');
  await wrapper.find('button').trigger('click');
  expect(wrapper.find('button').attributes('aria-expanded')).toBe('true');
  expect(wrapper.text()).toContain('<img src=x onerror=alert(1)>');
  expect(wrapper.find('img').exists()).toBe(false);
  expect(wrapper.findAll('a').map(link => link.attributes('href'))).toEqual(['https://konf.vusa.lt/notes.pdf']);
});

describe('empty archive', () => {
  it('shows the empty state without a message disclosure', () => {
    const wrapper = mount(MailingListArchive, { props: { messages: [] } });
    expect(wrapper.find('button').exists()).toBe(false);
    expect(wrapper.text()).not.toBe('');
  });
});
