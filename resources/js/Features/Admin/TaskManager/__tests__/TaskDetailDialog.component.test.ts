import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import TaskDetailDialog from '../TaskDetailDialog.vue';

import { commonStubs } from '@/tests/stubs';
import type { TaskDisplayData } from '@/Composables/useTaskPresentation';
import { TaskActionType } from '@/Types/TaskTypes';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const makeTask = (overrides: Partial<TaskDisplayData> = {}): TaskDisplayData => ({
  id: 'task-1',
  name: 'Registruoti posėdį',
  taskable_type: 'institution',
  taskable_id: 'institution-1',
  taskable: { id: 'institution-1', name: 'MIF SA' },
  ...overrides,
});

const mountDialog = (task: TaskDisplayData) =>
  mount(TaskDetailDialog, {
    props: { open: true, task },
    global: { stubs: { ...commonStubs } },
  });

describe('assignees', () => {
  it('collapses a long assignee list into an expandable avatar group', () => {
    const users = Array.from({ length: 20 }, (_, index) => ({ id: `u${index}`, name: `User ${index}` }));
    const wrapper = mountDialog(makeTask({ users }));

    expect(wrapper.findComponent({ name: 'UsersAvatarGroup' }).props('max')).toBe(5);
    expect(wrapper.findComponent({ name: 'UsersAvatarGroup' }).props('expandable')).toBe(true);
  });
});

describe('periodicity gap action', () => {
  it('offers one report action for a periodicity gap task and emits it', async () => {
    const wrapper = mountDialog(makeTask({ action_type: TaskActionType.PeriodicityGap }));

    const report = wrapper.findAll('button')
      .find(button => button.text().includes('tasks.periodicity_gap.report_no_meeting'));
    await report!.trigger('click');

    expect(wrapper.emitted('report')).toBeTruthy();
  });

  it('leaves it out for any other task', () => {
    const wrapper = mountDialog(makeTask({ action_type: TaskActionType.Manual }));

    expect(wrapper.text()).not.toContain('tasks.periodicity_gap.report_no_meeting');
  });
});

describe('cancelled task', () => {
  it('says it was cancelled and why, and offers no report action', () => {
    const wrapper = mountDialog(makeTask({
      action_type: TaskActionType.PeriodicityGap,
      can_be_manually_completed: false,
      completed_at: '2026-10-01T10:00:00Z',
      cancelled_at: '2026-10-01T10:00:00Z',
    }));

    expect(wrapper.get('[data-slot="task-cancelled"]').text()).toContain('tasks.cancelled');
    expect(wrapper.text()).toContain('tasks.cancelled_hint');
    expect(wrapper.text()).not.toContain('tasks.auto_completing');
    expect(wrapper.text()).not.toContain('tasks.periodicity_gap.report_no_meeting');
  });
});

describe('status', () => {
  it.each([
    [{ is_overdue: true, completed_at: '2026-10-01T10:00:00Z' }, 'success'],
    [{ is_overdue: true }, 'danger'],
    [{}, 'info'],
  ])('colours the task mark by the most final status (%j)', (overrides, role) => {
    const wrapper = mountDialog(makeTask(overrides));

    expect(wrapper.find('[data-slot="status-badge"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="status-mark"]').attributes('data-status-role')).toBe(role);
  });
});

describe('actions', () => {
  it('hands the page its own actions back and keeps report on its own event', async () => {
    const wrapper = mount(TaskDetailDialog, {
      props: { open: true, task: makeTask(), actions: [
        { key: 'report', label: 'Pranešti', shortLabel: 'Pranešti', primary: true },
        { key: 'delete', label: 'Ištrinti', shortLabel: 'Ištrinti', destructive: true },
      ] },
      global: { stubs: { ...commonStubs } },
    });

    await wrapper.get('[data-slot="preview-actions"] [data-action="report"]').trigger('click');

    expect(wrapper.emitted('report')).toHaveLength(1);
    expect(wrapper.emitted('action')).toBeUndefined();
  });
});
