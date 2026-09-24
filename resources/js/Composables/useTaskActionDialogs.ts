import { ref } from 'vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import type { TaskDisplayData } from '@/Composables/useTaskPresentation';

/**
 * Shared wiring for what a task list asks its page to open: the task detail dialog, and the
 * action window's "Pranešti apie veiklą" choice for a periodicity-gap task (record a meeting,
 * or say there was none). Render a `TaskDetailDialog` from the state this returns (see
 * Pages/Admin/Tasks/IndexTask.vue for the reference wiring).
 */
export function useTaskActionDialogs() {
  const actionWindow = useActionWindow();

  const showTaskDetail = ref(false);
  const selectedDetailTask = ref<TaskDisplayData | null>(null);

  const openReportWindow = (task: TaskDisplayData) => {
    // An orphaned task has no institution left to report on.
    if (!task.taskable) {
      return;
    }

    actionWindow.open({
      flow: 'institution.report',
      institution: { id: String(task.taskable_id), name: task.taskable.name ?? '' },
    });
  };

  const openTaskDetail = (task: TaskDisplayData) => {
    selectedDetailTask.value = task;
    showTaskDetail.value = true;
  };

  const closeTaskDetail = () => {
    showTaskDetail.value = false;
    selectedDetailTask.value = null;
  };

  const reportFromDetail = () => {
    const task = selectedDetailTask.value;
    if (!task) {
      return;
    }

    closeTaskDetail();
    openReportWindow(task);
  };

  return {
    showTaskDetail,
    selectedDetailTask,
    openReportWindow,
    openTaskDetail,
    closeTaskDetail,
    reportFromDetail,
  };
}
