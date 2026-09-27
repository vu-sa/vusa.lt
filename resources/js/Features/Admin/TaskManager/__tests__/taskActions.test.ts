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

  it('links an open agenda task to the meeting agenda, adding items only while it is being created', () => {
    const creation = task({ taskable_type: 'meeting', taskable_id: 'meeting-1', action_type: TaskActionType.AgendaCreation, can_be_manually_completed: false });
    const completion = { ...creation, action_type: TaskActionType.AgendaCompletion };

    const [add] = getTaskActions(creation);
    const [view] = getTaskActions(completion);

    expect(add).toMatchObject({ key: 'agenda', label: 'tasks.agenda.action_add_items' });
    expect(add.href).toContain('action=add');
    expect(view).toMatchObject({ key: 'agenda', label: 'tasks.agenda.action_view_agenda' });
    expect(view.href).not.toContain('action=add');
    expect(keys(getTaskActions({ ...completion, completed_at: '2026-01-01T00:00:00Z' }))).toEqual([]);
  });

  it('offers reopening a completed manual task and deletion only where it would succeed', () => {
    const done = task({ action_type: TaskActionType.Manual, completed_at: '2026-01-01T00:00:00Z' });

    expect(getTaskActions(done)).toEqual([expect.objectContaining({ key: 'complete', label: 'tasks.collection.reopen' })]);
    expect(keys(getTaskActions({ ...done, can_delete: false }))).not.toContain('delete');
  });

  it('leaves completing out for someone who may read the task but not update it', () => {
    const manual = task({ action_type: TaskActionType.Manual, can_update: false });

    expect(keys(getTaskActions(manual))).not.toContain('complete');
  });
});

