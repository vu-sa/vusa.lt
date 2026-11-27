import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import {
  formatTaskDueDate,
  getDueDateUrgencyClasses,
  getMeetingAgendaUrl,
  getSuggestedCheckInRange,
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

beforeEach(() => vi.useFakeTimers({ now: new Date('2026-04-15T12:00:00Z'), toFake: ['Date'] }));
afterEach(() => vi.useRealTimers());

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
    expect(getTaskActionBadgeClasses(null)).toContain('muted-foreground');
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
    const farOff = '2026-05-15T12:00:00Z';

    expect(formatTaskDueDate(farOff, 'en')).toBe('2026-05-15');
  });

  it('names a near due date by calendar day', () => {
    const inThreeDays = '2026-04-18';

    expect(formatTaskDueDate('2026-04-15', 'lt')).toBe('šiandien');
    expect(formatTaskDueDate(inThreeDays, 'lt')).toBe('po 3 d.');
  });

  it('returns an empty string when there is no due date', () => {
    expect(formatTaskDueDate(null, 'en')).toBe('');
  });

  it('warns only for a due date landing within three days and not yet overdue', () => {
    const inTwoDays = '2026-04-17T12:00:00.000Z';
    const inTenDays = '2026-04-25T12:00:00.000Z';

    expect(getDueDateUrgencyClasses({ due_date: inTwoDays, is_overdue: false })).toContain('status-attention');
    expect(getDueDateUrgencyClasses({ due_date: inTenDays, is_overdue: false })).toBe('');
    expect(getDueDateUrgencyClasses({ due_date: inTwoDays, is_overdue: true })).toBe('');
  });
});

describe('check-in date suggestion', () => {
  it('never suggests an end before the start, even for a long-overdue task', () => {
    // The bug this guards: a periodicity-gap task's due_date is a deadline (created as
    // "today + N days"), not the start of the reporting gap. Using it as the suggested end
    // date put the end before "today" (the start) as soon as the task went overdue — which is
    // the ordinary case, since these tasks are usually opened *because* they are overdue.
    const longOverdueCreatedAt = '2026-01-15T12:00:00.000Z';
    const { start, end } = getSuggestedCheckInRange({
      created_at: longOverdueCreatedAt,
      metadata: { effective_days_since_activity: 120 },
    });

    expect(start.getTime()).toBeLessThanOrEqual(end.getTime());
  });

  it('reconstructs the gap start from how long the institution had already gone without activity', () => {
    const createdAt = new Date('2026-01-15T00:00:00.000Z');
    const { start } = getSuggestedCheckInRange({
      created_at: createdAt.toISOString(),
      metadata: { effective_days_since_activity: 10 },
    });

    expect(start.toISOString()).toBe('2026-01-05T00:00:00.000Z');
  });

  it('ends today regardless of how the start was derived', () => {
    const { end } = getSuggestedCheckInRange({
      created_at: new Date().toISOString(),
      metadata: { effective_days_since_activity: 5 },
    });

    expect(end.toISOString()).toBe('2026-04-15T12:00:00.000Z');
  });

  it('falls back to a 14-day window when the task carries neither field', () => {
    const { start, end } = getSuggestedCheckInRange(null);

    expect([start.toISOString(), end.toISOString()]).toEqual(['2026-04-01T12:00:00.000Z', '2026-04-15T12:00:00.000Z']);
  });

  it('falls back to created_at alone when metadata has no usable count', () => {
    const createdAt = '2026-04-10T12:00:00.000Z';
    const { start } = getSuggestedCheckInRange({ created_at: createdAt, metadata: {} });

    expect(start.toISOString()).toBe('2026-04-10T12:00:00.000Z');
  });
});
