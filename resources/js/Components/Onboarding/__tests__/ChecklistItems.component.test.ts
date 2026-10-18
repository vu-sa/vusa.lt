import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import ChecklistItems from '../ChecklistItems.vue';
import ChecklistProgress from '../ChecklistProgress.vue';
import { checklistFixture } from './fixtures';

describe('ChecklistItems', () => {
  it('expands the first pending item or an explicitly requested item', () => {
    const items = checklistFixture({ access: 'done' }).items;
    expect(mount(ChecklistItems, { props: { items, saving: false } }).find('details[open]').attributes('data-item')).toBe('contacts');
    expect(mount(ChecklistItems, { props: { items, saving: false, initialItem: 'push' } }).find('details[open]').attributes('data-item')).toBe('push');
  });

  it('requires an explicit decision and keeps notification opt out separate from completion', async () => {
    const wrapper = mount(ChecklistItems, { props: { items: checklistFixture().items, saving: false } });
    expect(wrapper.emitted('update')).toBeUndefined();
    expect(wrapper.find('[data-testid="complete-push"]').exists()).toBe(false);
    await wrapper.find('[data-testid="skip-push"]').trigger('click');
    expect(wrapper.emitted('update')).toEqual([['push', 'skipped']]);
    await wrapper.find('[data-testid="complete-access"]').trigger('click');
    expect(wrapper.emitted('update')?.[1]).toEqual(['access', 'done']);
  });

  it('allows a declined step to be reopened and prevents duplicate decisions during a save', async () => {
    const wrapper = mount(ChecklistItems, { props: { items: checklistFixture({ push: 'skipped' }).items, saving: false } });
    await wrapper.find('[data-testid="reopen-push"]').trigger('click');
    expect(wrapper.emitted('update')).toEqual([['push', 'pending']]);
    await wrapper.setProps({ saving: true });
    expect(wrapper.find('[data-testid="reopen-push"]').attributes('disabled')).toBeDefined();
  });

  it('exposes resolved progress to assistive technology', () => {
    const wrapper = mount(ChecklistProgress, { props: { resolved: 3, total: 6, label: '3 of 6' } });
    const progress = wrapper.find('[role="progressbar"]');
    expect(progress.attributes('aria-valuenow')).toBe('3');
    expect(progress.attributes('aria-valuemax')).toBe('6');
    expect(progress.attributes('aria-label')).toBe('3 of 6');
  });
});
