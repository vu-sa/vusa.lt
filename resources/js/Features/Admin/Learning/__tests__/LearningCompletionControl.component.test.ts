import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { LearningImportance } from '@/Types/enums';
import LearningCompletionControl from '../LearningCompletionControl.vue';
import { commonStubs } from '@/tests/stubs';
import { step } from './fixtures';

describe('LearningCompletionControl', () => {
  it('uses a dashed optional outline and keeps the pointer on the visible control', async () => {
    const wrapper = mount(LearningCompletionControl, { props: { step: step({ importance: LearningImportance.Optional }), modelValue: false } });
    const checkbox = wrapper.get('[role="checkbox"]');
    expect(checkbox.classes()).toContain('border-dashed');
    expect(checkbox.classes()).toContain('cursor-pointer');
    expect(checkbox.classes()).toContain('pointer-coarse:before:-inset-2.5');
    expect(wrapper.classes()).not.toContain('cursor-pointer');
    expect(checkbox.classes()).toContain('enabled:hover:bg-muted');
    await checkbox.trigger('click');
    expect(wrapper.emitted('update:modelValue')).toEqual([[true]]);
    wrapper.unmount();
  });

  it.each([false, true])('shows automatic tracking inside pending and completed controls: %s', async (checked) => {
    const wrapper = mount(LearningCompletionControl, { props: { step: step({ automaticallyTracked: true, autoCompleted: checked }), modelValue: checked }, global: { stubs: commonStubs } });
    expect(wrapper.get('[role="img"]').attributes('aria-label')).toBe(checked ? 'learning.path.auto_help' : 'learning.path.auto_pending');
    expect(wrapper.get('[role="checkbox"]').attributes('aria-checked')).toBe(String(checked));
    expect(wrapper.get('[role="img"]').classes().includes('fill-current')).toBe(checked);
    expect(wrapper.get('[role="checkbox"]').attributes('aria-readonly')).toBe('true');
    expect(wrapper.find('[data-slot="checkbox-indicator"]').exists()).toBe(false);
    await wrapper.get('[role="checkbox"]').trigger('click');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    wrapper.unmount();
  });

  it('uses brand colors for essential borders, automatic lightning and completed fills', async () => {
    const wrapper = mount(LearningCompletionControl, { props: { step: step({ importance: LearningImportance.Essential, automaticallyTracked: true }), modelValue: false }, global: { stubs: commonStubs } });
    expect(wrapper.get('[role="checkbox"]').classes()).toContain('text-brand');
    await wrapper.setProps({ modelValue: true });
    expect(wrapper.get('[role="checkbox"]').classes()).toContain('bg-brand-fill');
    expect(wrapper.get('[role="checkbox"]').classes()).toContain('text-brand-foreground');
    await wrapper.setProps({ step: step({ importance: LearningImportance.Essential }) });
    expect(wrapper.get('[role="checkbox"]').classes()).toContain('data-[state=checked]:bg-brand-fill');
    wrapper.unmount();
  });

  it('renders the normal check icon for completed manual steps', () => {
    const wrapper = mount(LearningCompletionControl, { props: { step: step(), modelValue: true } });
    expect(wrapper.find('[data-slot="checkbox-indicator"] svg').exists()).toBe(true);
    expect(wrapper.find('[role="img"]').exists()).toBe(false);
    wrapper.unmount();
  });
});
