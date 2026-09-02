import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';

import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import type { TaskDisplayData } from '@/Composables/useTaskPresentation';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const makeTask = (overrides: Partial<TaskDisplayData> = {}): TaskDisplayData => ({
  id: 'task-1',
  name: 'Report institution activity',
  taskable_type: 'institution',
  taskable_id: 'institution-1',
  taskable: { id: 'institution-1', name: 'MIF SA' },
  ...overrides,
});

/** useActionWindow() needs an active component instance to inject from. */
const withComposable = () => {
  let result!: ReturnType<typeof useTaskActionDialogs>;

  mount(defineComponent({
    setup() {
      result = useTaskActionDialogs();
      return () => h('div');
    },
  }));

  return result;
};

describe('useTaskActionDialogs', () => {
  it('opens and closes the check-in dialog for the clicked task', () => {
    const dialogs = withComposable();
    const task = makeTask();

    dialogs.openCheckInDialog(task);
    expect(dialogs.showCheckInDialog.value).toBe(true);
    expect(dialogs.selectedCheckInTask.value).toStrictEqual(task);

    dialogs.closeCheckInDialog();
    expect(dialogs.showCheckInDialog.value).toBe(false);
    expect(dialogs.selectedCheckInTask.value).toBeNull();
  });

  it('opens and closes the task detail dialog for the clicked task', () => {
    const dialogs = withComposable();
    const task = makeTask();

    dialogs.openTaskDetail(task);
    expect(dialogs.showTaskDetail.value).toBe(true);
    expect(dialogs.selectedDetailTask.value).toStrictEqual(task);

    dialogs.closeTaskDetail();
    expect(dialogs.showTaskDetail.value).toBe(false);
    expect(dialogs.selectedDetailTask.value).toBeNull();
  });

  it('does nothing for openMeetingModal when the task has no subject left', () => {
    // An orphaned task (its subject was hard-deleted) has nothing for the action window to
    // open a meeting-creation flow against.
    const dialogs = withComposable();

    expect(() => dialogs.openMeetingModal(makeTask({ taskable: null }))).not.toThrow();
  });

  it('routes the detail dialog\'s "schedule meeting" into the meeting flow, closing detail first', () => {
    const dialogs = withComposable();
    const task = makeTask();

    dialogs.openTaskDetail(task);
    dialogs.scheduleMeetingFromDetail();

    expect(dialogs.showTaskDetail.value).toBe(false);
    expect(dialogs.selectedDetailTask.value).toBeNull();
  });

  it('routes the detail dialog\'s "report no meeting" into the check-in dialog, closing detail first', () => {
    const dialogs = withComposable();
    const task = makeTask();

    dialogs.openTaskDetail(task);
    dialogs.reportNoMeetingFromDetail();

    expect(dialogs.showTaskDetail.value).toBe(false);
    expect(dialogs.showCheckInDialog.value).toBe(true);
    expect(dialogs.selectedCheckInTask.value).toStrictEqual(task);
  });

  it('derives the check-in date range from the selected task, not a fixed window', () => {
    const dialogs = withComposable();
    const overdueTask = makeTask({
      created_at: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString(),
      metadata: { effective_days_since_activity: 120 },
    });

    dialogs.openCheckInDialog(overdueTask);

    expect(dialogs.checkInStartDate.value.getTime()).toBeLessThanOrEqual(dialogs.checkInEndDate.value.getTime());
  });
});
