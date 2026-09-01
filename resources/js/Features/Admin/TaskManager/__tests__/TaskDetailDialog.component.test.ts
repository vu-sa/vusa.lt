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
  it('collapses a long assignee list into an avatar group with a count', () => {
    // Twenty full-width name pills used to push the dialog past the height of the screen.
    const users = Array.from({ length: 20 }, (_, index) => ({ id: `u${index}`, name: `User ${index}` }));
    const wrapper = mountDialog(makeTask({ users }));

    expect(wrapper.findComponent({ name: 'UsersAvatarGroup' }).props('max')).toBe(8);
    expect(wrapper.text()).toContain('20');
  });
});

describe('periodicity gap actions', () => {
  it('offers the schedule and report actions for a periodicity gap task', () => {
    // The predicate used to compare against 'PeriodicityGap', which the enum never emits,
    // so this section never rendered.
    const wrapper = mountDialog(makeTask({ action_type: TaskActionType.PeriodicityGap }));

    expect(wrapper.text()).toContain('tasks.periodicity_gap.schedule_meeting');
    expect(wrapper.text()).toContain('tasks.periodicity_gap.report_no_meeting');
  });

  it('leaves them out for any other task', () => {
    const wrapper = mountDialog(makeTask({ action_type: TaskActionType.Manual }));

    expect(wrapper.text()).not.toContain('tasks.periodicity_gap.schedule_meeting');
  });

  it('emits scheduleMeeting when the action is taken', async () => {
    const wrapper = mountDialog(makeTask({ action_type: TaskActionType.PeriodicityGap }));

    const scheduleButton = wrapper.findAll('button')
      .find(button => button.text().includes('tasks.periodicity_gap.schedule_meeting'));
    await scheduleButton!.trigger('click');

    expect(wrapper.emitted('scheduleMeeting')).toBeTruthy();
  });
});
