import { describe, expect, it } from 'vitest';
import { enUS } from 'date-fns/locale';

import {
  formatTaskDueDate,
  getDueDateUrgencyClasses,
  getMeetingAgendaUrl,
  getTaskActionBadgeClasses,
  getTaskActionIcon,
  getTaskActionLabel,
  getTaskableUrl,
  isAgendaCreationTask,
  isAgendaTask,
  isOrphanedTask,
  isPeriodicityGapTask,
  type TaskDisplayData,
} from '@/Composables/useTaskPresentation';
import { TaskActionType } from '@/Types/TaskTypes';

const makeTask = (overrides: Partial<TaskDisplayData> = {}): TaskDisplayData => ({
  id: 'task-1',
  name: 'Task',
  taskable_type: 'meeting',
  taskable_id: 'meeting-1',
  taskable: { id: 'meeting-1', name: 'Posėdis' },
  ...overrides,
});

describe('action type presentation', () => {
  it('gives each action type its own icon', () => {
    const icons = [
      TaskActionType.Approval,
      TaskActionType.Pickup,
      TaskActionType.Return,
      TaskActionType.PeriodicityGap,
      TaskActionType.AgendaCreation,
      TaskActionType.AgendaCompletion,
    ].map(getTaskActionIcon);

    expect(new Set(icons).size).toBe(icons.length);
  });

  it('falls back to the neutral palette for an unknown action type', () => {
    expect(getTaskActionBadgeClasses('something-new')).toBe(getTaskActionBadgeClasses(null));
    expect(getTaskActionBadgeClasses(null)).toContain('zinc');
  });

  it('labels only the action types the UI calls automatic', () => {
    expect(getTaskActionLabel(TaskActionType.Approval)).not.toBe('');
    expect(getTaskActionLabel(TaskActionType.Manual)).toBe('');
    expect(getTaskActionLabel(null)).toBe('');
  });
});

describe('task predicates', () => {
  it('recognises periodicity gap and agenda tasks', () => {
    expect(isPeriodicityGapTask(makeTask({ action_type: TaskActionType.PeriodicityGap }))).toBe(true);
    expect(isPeriodicityGapTask(makeTask({ action_type: TaskActionType.Manual }))).toBe(false);

    expect(isAgendaTask(makeTask({ action_type: TaskActionType.AgendaCompletion }))).toBe(true);
    expect(isAgendaCreationTask(makeTask({ action_type: TaskActionType.AgendaCompletion }))).toBe(false);
    expect(isAgendaCreationTask(makeTask({ action_type: TaskActionType.AgendaCreation }))).toBe(true);
  });

  it('treats a task with no taskable as orphaned', () => {
    expect(isOrphanedTask(makeTask({ taskable: null }))).toBe(true);
    expect(isOrphanedTask(makeTask())).toBe(false);
  });
});

describe('links', () => {
  it('returns no subject link for an orphaned task', () => {
    expect(getTaskableUrl(makeTask({ taskable: null }))).toBeNull();
  });

  it('returns no subject link for an unmapped taskable type', () => {
    expect(getTaskableUrl(makeTask({ taskable_type: 'something-else' }))).toBeNull();
  });

  it('links a meeting task to its subject and its agenda', () => {
    expect(getTaskableUrl(makeTask())).toContain('meetings.show');
    expect(getMeetingAgendaUrl(makeTask({ action_type: TaskActionType.AgendaCompletion }))).toContain('tab=agenda');
  });

  it('opens the add-item flow only for an agenda creation task', () => {
    expect(getMeetingAgendaUrl(makeTask({ action_type: TaskActionType.AgendaCreation }))).toContain('action=add');
    expect(getMeetingAgendaUrl(makeTask({ action_type: TaskActionType.AgendaCompletion }))).not.toContain('action=add');
  });

  it('returns no agenda link for a non-meeting task', () => {
    expect(getMeetingAgendaUrl(makeTask({ taskable_type: 'institution' }))).toBeNull();
  });
});

describe('due dates', () => {
  it('falls back to an absolute date beyond a week out', () => {
    const farOff = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString();

    expect(formatTaskDueDate(farOff, enUS)).toMatch(/^\d{4}-\d{2}-\d{2}$/);
  });

  it('returns an empty string when there is no due date', () => {
    expect(formatTaskDueDate(null, enUS)).toBe('');
  });

  it('warns only for a due date landing within three days and not yet overdue', () => {
    const inTwoDays = new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString();
    const inTenDays = new Date(Date.now() + 10 * 24 * 60 * 60 * 1000).toISOString();

    expect(getDueDateUrgencyClasses({ due_date: inTwoDays, is_overdue: false })).toContain('amber');
    expect(getDueDateUrgencyClasses({ due_date: inTenDays, is_overdue: false })).toBe('');
    expect(getDueDateUrgencyClasses({ due_date: inTwoDays, is_overdue: true })).toBe('');
  });
});
