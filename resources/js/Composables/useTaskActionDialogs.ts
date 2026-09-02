import { computed, ref } from 'vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { getSuggestedCheckInRange, type TaskDisplayData } from '@/Composables/useTaskPresentation';

/**
 * Shared wiring for the three modals a `TaskManager` instance asks its page to open:
 * scheduling a meeting, reporting no meeting (a check-in), and the task detail dialog.
 *
 * Every `TaskManager` consumer needs all three bound or its periodicity-gap quick actions and
 * "View details" silently do nothing — several pages shipped without ever wiring them up. Bind
 * the returned handlers to `TaskManager`'s `@open-meeting-modal`, `@open-check-in-dialog`, and
 * `@open-task-detail`, then render `<AddCheckInDialog>` / a `TaskDetailDialog` from the state
 * this returns (see ShowTasks.vue for the reference wiring).
 */
export function useTaskActionDialogs() {
  const actionWindow = useActionWindow();

  const showCheckInDialog = ref(false);
  const showTaskDetail = ref(false);
  const selectedCheckInTask = ref<TaskDisplayData | null>(null);
  const selectedDetailTask = ref<TaskDisplayData | null>(null);

  const checkInRange = computed(() => getSuggestedCheckInRange(selectedCheckInTask.value));
  const checkInStartDate = computed(() => checkInRange.value.start);
  const checkInEndDate = computed(() => checkInRange.value.end);

  const openMeetingModal = (task: TaskDisplayData) => {
    if (!task.taskable) {
      return;
    }

    actionWindow.open({
      flow: 'meeting.create',
      institution: { id: task.taskable_id, name: task.taskable.name } as App.Entities.Institution,
    });
  };

  const openCheckInDialog = (task: TaskDisplayData) => {
    selectedCheckInTask.value = task;
    showCheckInDialog.value = true;
  };

  const closeCheckInDialog = () => {
    showCheckInDialog.value = false;
    selectedCheckInTask.value = null;
  };

  const openTaskDetail = (task: TaskDisplayData) => {
    selectedDetailTask.value = task;
    showTaskDetail.value = true;
  };

  const closeTaskDetail = () => {
    showTaskDetail.value = false;
    selectedDetailTask.value = null;
  };

  const scheduleMeetingFromDetail = () => {
    const task = selectedDetailTask.value;
    if (!task) {
      return;
    }

    closeTaskDetail();
    openMeetingModal(task);
  };

  const reportNoMeetingFromDetail = () => {
    const task = selectedDetailTask.value;
    if (!task) {
      return;
    }

    closeTaskDetail();
    openCheckInDialog(task);
  };

  return {
    showCheckInDialog,
    showTaskDetail,
    selectedCheckInTask,
    selectedDetailTask,
    checkInStartDate,
    checkInEndDate,
    openMeetingModal,
    openCheckInDialog,
    closeCheckInDialog,
    openTaskDetail,
    closeTaskDetail,
    scheduleMeetingFromDetail,
    reportNoMeetingFromDetail,
  };
}
