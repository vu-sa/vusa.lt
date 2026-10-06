import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';

import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';
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

/** The composable injects the action window, so it needs a provider above it. */
const withComposable = () => {
  let result!: ReturnType<typeof useTaskActionDialogs>;
  let window!: ActionWindowContext;

  const Child = defineComponent({
    setup() {
      result = useTaskActionDialogs();
      return () => h('div');
    },
  });

  mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      return () => h(Child);
    },
  }));

  return { dialogs: result, window };
};

describe('useTaskActionDialogs', () => {
  it('opens and closes the task detail dialog for the clicked task', () => {
    const { dialogs } = withComposable();
    const task = makeTask();

    dialogs.openTaskDetail(task);
    expect(dialogs.showTaskDetail.value).toBe(true);
    expect(dialogs.selectedDetailTask.value).toStrictEqual(task);

    dialogs.closeTaskDetail();
    expect(dialogs.showTaskDetail.value).toBe(false);
    expect(dialogs.selectedDetailTask.value).toBeNull();
  });

  it('opens the action window on the report choice for the task\'s institution', () => {
    const { dialogs, window } = withComposable();

    dialogs.openReportWindow(makeTask());

    expect(window.isOpen.value).toBe(true);
    expect(window.current.value.id).toBe('institution.report');
    expect(window.draft.institution).toEqual({ id: 'institution-1', name: 'MIF SA' });
  });

  it('does nothing for a task whose subject is gone', () => {
    const { dialogs, window } = withComposable();

    dialogs.openReportWindow(makeTask({ taskable: null }));

    expect(window.isOpen.value).toBe(false);
  });

  it('closes the detail dialog before reporting from it', () => {
    const { dialogs, window } = withComposable();

    dialogs.openTaskDetail(makeTask());
    dialogs.reportFromDetail();

    expect(dialogs.showTaskDetail.value).toBe(false);
    expect(window.current.value.id).toBe('institution.report');
  });
});
