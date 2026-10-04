import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import NotificationTypeRow from '../NotificationTypeRow.vue';

import { NativeSelect } from '@/Components/ui/native-select';

const type = {
  value: 'task_reminder',
  section: 'task',
  sectionModelEnumKey: 'TASK',
  lockedEmail: null,
  email: 'immediate' as const,
  push: true,
};

const mountRow = (props: Record<string, unknown> = {}) => mount(NotificationTypeRow, {
  props: { type, email: 'immediate', push: true, pushAvailable: true, ...props },
});

describe('NotificationTypeRow', () => {
  it('passes the email choice to the select and emits a new one', async () => {
    const wrapper = mountRow();
    const select = wrapper.findComponent(NativeSelect);

    expect(select.props('modelValue')).toBe('immediate');

    select.vm.$emit('update:modelValue', 'digest');

    expect(wrapper.emitted('update:email')?.[0]).toEqual(['digest']);
  });

  it('passes the active delivery icon to NativeSelect', () => {
    const wrapper = mountRow({ email: 'immediate' });
    const select = wrapper.findComponent(NativeSelect);
    expect(select.props('icon')).toBeDefined();
  });

  it('toggles push while a device is connected', async () => {
    const wrapper = mountRow();
    const toggle = wrapper.find('[data-testid="push-toggle"]');

    expect(toggle.attributes('aria-pressed')).toBe('true');

    await toggle.trigger('click');

    expect(wrapper.emitted('update:push')?.[0]).toEqual([false]);
  });

  it('shows the locked note instead of a select for role-inbox mail', () => {
    const wrapper = mountRow({ type: { ...type, lockedEmail: 'immediate' } });

    expect(wrapper.find('[data-testid="email-locked"]').exists()).toBe(true);
    expect(wrapper.findComponent(NativeSelect).exists()).toBe(false);
  });
});
