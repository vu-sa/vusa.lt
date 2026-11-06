import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { CalendarPlus } from '@lucide/vue';

import ActionChoiceButton from '@/Components/ActionWindow/ActionChoiceButton.vue';

/**
 * The one primitive every action window screen is built from, so its contract —
 * a title, an optional explanation, and a single click — is worth pinning.
 */
describe('ActionChoiceButton.vue', () => {
  const mountButton = (props: Record<string, unknown> = {}) =>
    mount(ActionChoiceButton, {
      props: { title: 'Pranešti apie posėdį', ...props },
    });

  it('renders the title and its explanation', () => {
    const wrapper = mountButton({ description: 'Sukursime posėdį' });

    expect(wrapper.text()).toContain('Pranešti apie posėdį');
    expect(wrapper.text()).toContain('Sukursime posėdį');
  });

  it('omits the description line entirely when there is nothing to say', () => {
    expect(mountButton().text()).toBe('Pranešti apie posėdį');
  });

  it('renders an icon when given one', () => {
    expect(mountButton({ icon: CalendarPlus }).find('svg.lucide-calendar-plus').exists()).toBe(true);
  });

  it('emits a click', async () => {
    const wrapper = mountButton();
    await wrapper.trigger('click');

    expect(wrapper.emitted('click')).toHaveLength(1);
  });

  it('does not emit when disabled', async () => {
    const wrapper = mountButton({ disabled: true });
    await wrapper.trigger('click');

    expect(wrapper.emitted('click')).toBeUndefined();
  });

  it('is a step with a chevron by default, never a check box', () => {
    const step = mountButton();

    expect(step.find('svg.lucide-chevron-right').exists()).toBe(true);
    expect(step.find('svg.lucide-check').exists()).toBe(false);
    expect(step.attributes('aria-pressed')).toBeUndefined();
  });

  it('marks the earlier answer of a step without turning it into a check box', () => {
    const step = mountButton({ selected: true });

    expect(step.attributes('aria-current')).toBe('true');
    expect(step.find('svg.lucide-chevron-right').exists()).toBe(true);
    expect(step.find('svg.lucide-check').exists()).toBe(false);
  });

  it('shows a check box for a choice toggled in place', () => {
    expect(mountButton({ indicator: 'check' }).attributes('aria-pressed')).toBe('false');

    const picked = mountButton({ indicator: 'check', selected: true });
    expect(picked.attributes('aria-pressed')).toBe('true');
    expect(picked.find('svg.lucide-check').exists()).toBe(true);
    expect(picked.find('svg.lucide-chevron-right').exists()).toBe(false);
  });

  it('a description slot overrides the prop, so screens can compose their own', () => {
    const wrapper = mount(ActionChoiceButton, {
      props: { title: 'MIF SPK', description: 'ignored' },
      slots: { description: '<span>Paskutinis posėdis prieš 45 d.</span>' },
    });

    expect(wrapper.text()).toContain('Paskutinis posėdis prieš 45 d.');
    expect(wrapper.text()).not.toContain('ignored');
  });
});
