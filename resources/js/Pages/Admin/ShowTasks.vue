<template>
  <AdminContentPage :title="$t('Užduotys')">
    <!-- Stats overview cards -->
    <div v-if="taskStats" class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Total pending -->
      <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
            <ClipboardListIcon class="h-5 w-5 text-zinc-600 dark:text-zinc-400" />
          </div>
          <div>
            <p class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ taskStats.total }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('tasks.stats.pending') }}</p>
          </div>
        </div>
      </div>
      
      <!-- Overdue -->
      <div :class="[
        'relative overflow-hidden rounded-xl border p-4',
        taskStats.overdue > 0 
          ? 'border-red-200/60 bg-red-50/30 dark:border-red-900/30 dark:bg-red-950/10' 
          : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900'
      ]">
        <div class="flex items-center gap-3">
          <div :class="[
            'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
            taskStats.overdue > 0 
              ? 'bg-red-100 dark:bg-red-900/30' 
              : 'bg-zinc-100 dark:bg-zinc-800'
          ]">
            <AlertCircleIcon :class="[
              'h-5 w-5',
              taskStats.overdue > 0 
                ? 'text-red-600 dark:text-red-400' 
                : 'text-zinc-400'
            ]" />
          </div>
          <div>
            <p class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ taskStats.overdue }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('overdue') }}</p>
          </div>
        </div>
      </div>
      
      <!-- Auto-completing -->
      <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
            <RotateCwIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <p class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ taskStats.autoCompleting }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('tasks.stats.auto_completing') }}</p>
          </div>
        </div>
      </div>
      
      <!-- Completed -->
      <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
            <CheckCircleIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
          </div>
          <div>
            <p class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ taskStats.completed }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('completed') }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Task manager with table -->
    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
      <TaskManager 
        :tasks="tasks" 
        :task-stats="taskStats"
        @open-meeting-modal="handleOpenMeetingModal"
        @open-check-in-dialog="handleOpenCheckInDialog"
        @open-task-detail="handleOpenTaskDetail"
      />
    </div>

    <!-- Meeting modal for periodicity gap tasks -->
    <NewMeetingDialog
      v-if="selectedInstitution"
      :show-modal="showMeetingModal"
      :institution="selectedInstitution"
      @close="closeMeetingModal"
    />

    <!-- Check-in dialog for periodicity gap tasks -->
    <AddCheckInDialog
      v-if="selectedCheckInTask"
      :open="showCheckInDialog"
      :institution-id="selectedCheckInTask.taskable_id"
      :institution-name="selectedCheckInTask.taskable?.name"
      :initial-start-date="checkInStartDate"
      :initial-end-date="checkInEndDate"
      @close="closeCheckInDialog"
    />

    <!-- Task detail dialog -->
    <TaskDetailDialog
      v-if="selectedDetailTask"
      :open="showTaskDetail"
      :task="selectedDetailTask"
      @close="closeTaskDetail"
      @schedule-meeting="handleScheduleMeetingFromDetail"
      @report-no-meeting="handleReportNoMeetingFromDetail"
    />
  </AdminContentPage>
</template>

<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from "vue";
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import { usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import { trans as $t } from "laravel-vue-i18n";
import Icons from "@/Types/Icons/regular";
import { 
  ClipboardList as ClipboardListIcon,
  AlertCircle as AlertCircleIcon,
  RotateCw as RotateCwIcon,
  CheckCircle as CheckCircleIcon,
} from "lucide-vue-next";
import type { TaskProgress, TaskActionType } from "@/Types/TaskTypes";

// Lazy load modals
const NewMeetingDialog = defineAsyncComponent(() => import("@/Components/Dialogs/NewMeetingDialog.vue"));
const AddCheckInDialog = defineAsyncComponent(() => import("@/Components/Institutions/AddCheckInDialog.vue"));
const TaskDetailDialog = defineAsyncComponent(() => import("@/Features/Admin/TaskManager/TaskDetailDialog.vue"));

interface TaskWithDetails {
  id: string;
  name: string;
  description?: string | null;
  due_date?: string | null;
  completed_at?: string | null;
  action_type?: TaskActionType | string | null;
  progress?: TaskProgress | null;
  is_overdue?: boolean;
  can_be_manually_completed?: boolean;
  taskable?: {
    id: string;
    name?: string;
    type?: string;
  } | null;
  taskable_type: string;
  taskable_id: string;
  users?: Array<{
    id: string;
    name: string;
    profile_photo_path?: string;
  }>;
}

interface TaskStats {
  total: number;
  completed: number;
  overdue: number;
  autoCompleting: number;
}

defineProps<{
  tasks: TaskWithDetails[];
  taskStats?: TaskStats;
}>();

// Modal state
const showMeetingModal = ref(false);
const showCheckInDialog = ref(false);
const showTaskDetail = ref(false);
const selectedMeetingTask = ref<TaskWithDetails | null>(null);
const selectedCheckInTask = ref<TaskWithDetails | null>(null);
const selectedDetailTask = ref<TaskWithDetails | null>(null);

// Computed institution for meeting modal
const selectedInstitution = computed(() => {
  if (!selectedMeetingTask.value?.taskable) return null;
  return {
    id: selectedMeetingTask.value.taskable_id,
    name: selectedMeetingTask.value.taskable.name,
  } as App.Entities.Institution;
});

// Computed dates for check-in dialog (autofill from today to task due date)
const checkInStartDate = computed(() => new Date());
const checkInEndDate = computed(() => {
  if (selectedCheckInTask.value?.due_date) {
    return new Date(selectedCheckInTask.value.due_date);
  }
  // Default to 2 weeks from now
  return new Date(Date.now() + 14 * 24 * 60 * 60 * 1000);
});

// Event handlers
const handleOpenMeetingModal = (task: TaskWithDetails) => {
  selectedMeetingTask.value = task;
  showMeetingModal.value = true;
};

const handleOpenCheckInDialog = (task: TaskWithDetails) => {
  selectedCheckInTask.value = task;
  showCheckInDialog.value = true;
};

const closeMeetingModal = () => {
  showMeetingModal.value = false;
  selectedMeetingTask.value = null;
};

const closeCheckInDialog = () => {
  showCheckInDialog.value = false;
  selectedCheckInTask.value = null;
};

// Task detail dialog handlers
const handleOpenTaskDetail = (task: TaskWithDetails) => {
  selectedDetailTask.value = task;
  showTaskDetail.value = true;
};

const closeTaskDetail = () => {
  showTaskDetail.value = false;
  selectedDetailTask.value = null;
};

const handleScheduleMeetingFromDetail = () => {
  if (selectedDetailTask.value) {
    closeTaskDetail();
    handleOpenMeetingModal(selectedDetailTask.value);
  }
};

const handleReportNoMeetingFromDetail = () => {
  if (selectedDetailTask.value) {
    closeTaskDetail();
    handleOpenCheckInDialog(selectedDetailTask.value);
  }
};

// Generate breadcrumbs
usePageBreadcrumbs([
  { label: $t('Užduotys'), icon: Icons.TASK }
]);
</script>
