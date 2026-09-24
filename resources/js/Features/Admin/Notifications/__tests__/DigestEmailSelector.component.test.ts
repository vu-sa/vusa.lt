import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import DigestEmailSelector from '../DigestEmailSelector.vue';

const availableEmails = [
  { email: 'user@example.com', label: 'User Email', type: 'user' as const },
  { email: 'duty@vusa.lt', label: 'Duty Email', type: 'duty' as const },
];

describe('DigestEmailSelector', () => {
  it('renders all available emails with labels', () => {
    const wrapper = mount(DigestEmailSelector, {
      props: {
        availableEmails,
        modelValue: ['user@example.com'],
      },
    });

    expect(wrapper.text()).toContain('user@example.com');
    expect(wrapper.text()).toContain('duty@vusa.lt');
  });

  it('allows clicking the whole item container to toggle selection', async () => {
    const wrapper = mount(DigestEmailSelector, {
      props: {
        availableEmails,
        modelValue: ['user@example.com'],
      },
    });

    // Find the second email item container
    const items = wrapper.findAll('[data-slot="digest-email-option"]');
    expect(items.length).toBe(2);

    // Clicking the second item label container
    await items[1].trigger('click');

    expect(wrapper.emitted('update:modelValue')).toBeTruthy();
    const emitted = wrapper.emitted('update:modelValue')!;
    expect(emitted[0][0]).toEqual(['user@example.com', 'duty@vusa.lt']);
  });
});
