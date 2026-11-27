import { afterEach, describe, expect, it } from 'vitest';

import type { TaskDisplayData } from '@/Composables/useTaskPresentation';
import { TaskActionType } from '@/Types/TaskTypes';
import { scopedGlobals } from '@/tests/helpers/scopedGlobals';

import { getTaskActions, getTaskRowActions } from '../taskActions';

const { stubGlobal, restoreGlobals } = scopedGlobals();
afterEach(restoreGlobals);

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

  it('leaves completing to the row checkbox but keeps the entity command', () => {
    const manual = task({ action_type: TaskActionType.Manual, can_delete: true });

    expect(keys(getTaskActions(manual))).toEqual(['complete', 'delete']);
    expect(keys(getTaskRowActions(manual))).toEqual(['open', 'delete']);
  });

  it('preserves the workflow primary and its short label', () => {
    const gap = task({ action_type: TaskActionType.PeriodicityGap, can_be_manually_completed: false, can_delete: true });
    const [open, first, second] = getTaskRowActions(gap);

    expect(open).toMatchObject({ key: 'open', role: 'open' });
    expect(first).toMatchObject({ key: 'report', shortLabel: 'tasks.short_actions.report', primary: true });
    expect(second).toMatchObject({ key: 'delete' });
    expect(second.primary).not.toBe(true);
  });

  it('links an open agenda task to the meeting agenda, adding items only while it is being created', () => {
    stubGlobal('route', (name: string, id: string) => `/mocked/${name}/${id}`);
    const creation = task({ taskable_type: 'meeting', taskable_id: 'meeting-1', taskable: { id: 'meeting-1', name: 'Posėdis' }, action_type: TaskActionType.AgendaCreation, can_be_manually_completed: false });
    const completion = { ...creation, action_type: TaskActionType.AgendaCompletion };

    const [add] = getTaskActions(creation);
    const [view] = getTaskActions(completion);

    expect(add).toMatchObject({ key: 'agenda', label: 'tasks.agenda.action_add_items' });
    expect(add.href).toBe('/mocked/meetings.show/meeting-1?tab=agenda&action=add');
    expect(view).toMatchObject({ key: 'agenda', label: 'tasks.agenda.action_view_agenda' });
    expect(view.href).toBe('/mocked/meetings.show/meeting-1?tab=agenda');
    expect(keys(getTaskActions({ ...completion, completed_at: '2026-01-01T00:00:00Z' }))).toEqual([]);
  });

  it.each([TaskActionType.AgendaCreation, TaskActionType.AgendaCompletion])('omits the agenda action when the meeting is missing (%s)', (action_type) => {
    const orphan = task({
      action_type,
      taskable_type: 'meeting',
      taskable_id: 'missing-meeting',
      taskable: null,
      can_be_manually_completed: false,
      can_delete: true,
    });

    expect(keys(getTaskActions(orphan))).toEqual(['delete']);
    expect(getTaskRowActions(orphan)).toMatchObject([{ key: 'open', primary: true }, { key: 'delete' }]);
    expect(keys(getTaskActions({ ...orphan, can_delete: false }))).toEqual([]);
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
