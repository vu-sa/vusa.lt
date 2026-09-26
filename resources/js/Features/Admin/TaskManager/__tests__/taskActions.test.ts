import { describe, expect, it } from 'vitest';

import { getTaskActions, getTaskRowActions } from '../taskActions';

import type { TaskDisplayData } from '@/Composables/useTaskPresentation';
import { TaskActionType } from '@/Types/TaskTypes';

const task = (overrides: Partial<TaskDisplayData> = {}): TaskDisplayData => ({
  id: 'task-1',
  name: 'Užduotis',
  taskable_type: 'institution',
  taskable_id: 'institution-1',
  taskable: { id: 'institution-1', name: 'MIF SA' },
  completed_at: null,
  ...overrides,
});

const keys = (actions: { key: string }[]) => actions.map(action => action.key);

describe('task actions', () => {
  it('offers one report action on an open periodicity gap, and nothing to tick', () => {
    const gap = task({ action_type: TaskActionType.PeriodicityGap, can_be_manually_completed: false });

    expect(keys(getTaskActions(gap))).toEqual(['report']);
    expect(keys(getTaskActions({ ...gap, completed_at: '2026-01-01T00:00:00Z' }))).toEqual([]);
  });

  it('leaves completing to the row checkbox but keeps it in the preview', () => {
    const manual = task({ action_type: TaskActionType.Manual, can_delete: true });

    expect(keys(getTaskActions(manual))).toEqual(['complete', 'delete']);
    expect(keys(getTaskRowActions(manual))).toEqual(['delete']);
  });

  it('labels only the first row button, with one word', () => {
    const gap = task({ action_type: TaskActionType.PeriodicityGap, can_be_manually_completed: false, can_delete: true });
    const [first, second] = getTaskRowActions(gap);

    expect(first).toMatchObject({ key: 'report', label: 'tasks.short_actions.report', labelled: true });
    expect(second).toMatchObject({ key: 'delete' });
    expect(second.labelled).toBeUndefined();
  });
});
