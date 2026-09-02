<template>
  <AdminContentPage :title="$t('Užduotys')">
    <TaskStatsCards v-if="taskStats" :task-stats />

    <!-- Task manager with table -->
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <div class="p-3 sm:p-6">
        <TaskManager
          :tasks="tasks.data"
          :task-stats
          :current-filter="currentStatus"
          :total-count="tasks.total"
          server-side-filter
          server-paginated
          @filter-change="handleFilterChange"
          @open-meeting-modal="openMeetingModal"
          @open-check-in-dialog="openCheckInDialog"
          @open-task-detail="openTaskDetail"
        />
      </div>

      <TaskPagination
        :current-page="tasks.current_page"
        :last-page="tasks.last_page"
        :total="tasks.total"
        :from="tasks.from"
        :to="tasks.to"
        @change="goToPage"
      />
    </div>

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
      @schedule-meeting="scheduleMeetingFromDetail"
      @report-no-meeting="reportNoMeetingFromDetail"
    />
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import type { TaskDisplayData, TaskStats } from '@/Composables/useTaskPresentation';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import TaskStatsCards from '@/Features/Admin/TaskManager/TaskStatsCards.vue';
import TaskPagination from '@/Features/Admin/TaskManager/TaskPagination.vue';
import { TaskIcon } from '@/Components/icons';

// Lazy load modals
const AddCheckInDialog = defineAsyncComponent(() => import('@/Components/Institutions/AddCheckInDialog.vue'));
const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));

interface PaginatedTasks {
  data: TaskDisplayData[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

const props = defineProps<{
  tasks: PaginatedTasks;
  taskStats?: TaskStats;
  status?: 'all' | 'completed' | 'incomplete';
}>();

// Current filter status from URL/props
const currentStatus = computed(() => props.status ?? 'incomplete');

// Pagination - preserve status filter
const goToPage = (page: number) => {
  router.get(route('userTasks'), {
    page,
    status: currentStatus.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

// Handle filter changes from TaskManager - reload with new status
const handleFilterChange = (status: 'all' | 'completed' | 'incomplete') => {
  router.get(route('userTasks'), {
    status,
    page: 1, // Reset to first page on filter change
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const {
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
} = useTaskActionDialogs();

// Generate breadcrumbs
usePageBreadcrumbs([
  { label: $t('Užduotys'), icon: TaskIcon },
]);
</script>
