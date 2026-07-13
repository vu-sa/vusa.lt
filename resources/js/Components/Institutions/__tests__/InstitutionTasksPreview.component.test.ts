import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionTasksPreview from '../InstitutionTasksPreview.vue';

const stubs = { UsersAvatarGroup: true };

const makeTasks = () => [
  { id: 'done', name: 'Completed task', completed_at: '2026-01-01T00:00:00Z', is_overdue: false },
  { id: 'overdue', name: 'Overdue task', completed_at: null, is_overdue: true },
  { id: 'soon', name: 'Due soon task', completed_at: null, is_overdue: false, due_date: new Date(Date.now() + 2 * 86400000).toISOString() },
  { id: 'later', name: 'Far future task', completed_at: null, is_overdue: false, due_date: new Date(Date.now() + 60 * 86400000).toISOString() },
];

describe('InstitutionTasksPreview', () => {
  it('excludes completed tasks', () => {
    const wrapper = mount(InstitutionTasksPreview, {
      props: { tasks: makeTasks() },
      global: { stubs },
    });

    expect(wrapper.text()).not.toContain('Completed task');
    expect(wrapper.text()).toContain('Overdue task');
  });

  it('shows an overdue badge and a due-soon badge', () => {
    const wrapper = mount(InstitutionTasksPreview, {
      props: { tasks: makeTasks() },
      global: { stubs },
    });

    // $t is mocked to return the key
    expect(wrapper.text()).toContain('Vėluoja');
    expect(wrapper.text()).toContain('Greitai');
  });

  it('orders overdue tasks before others', () => {
    const wrapper = mount(InstitutionTasksPreview, {
      props: { tasks: makeTasks() },
      global: { stubs },
    });

    const text = wrapper.text();
    expect(text.indexOf('Overdue task')).toBeLessThan(text.indexOf('Due soon task'));
  });

  it('renders the empty state when there are no open tasks', () => {
    const wrapper = mount(InstitutionTasksPreview, {
      props: { tasks: [{ id: 'done', name: 'Completed', completed_at: '2026-01-01T00:00:00Z' }] },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Nėra aktyvių užduočių');
  });
});
