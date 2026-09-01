<template>
  <AdminContentPage :title="$t('tasks.summary.title')">
    <!-- Filters row -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
      <!-- Type filter tabs -->
      <div class="flex flex-wrap items-center gap-2">
        <Button
          :variant="!filters.taskable_type ? 'default' : 'outline'"
          size="sm"
          @click="updateFilter('taskable_type', null)"
        >
          {{ $t('Visi') }}
          <Badge v-if="taskStats" variant="secondary" class="ml-1.5 tabular-nums">
            {{ taskStats.byType.institutions + taskStats.byType.reservations }}
          </Badge>
        </Button>
        <Button
          :variant="filters.taskable_type === 'institutions' ? 'default' : 'outline'"
          size="sm"
          @click="updateFilter('taskable_type', 'institutions')"
        >
          <BuildingIcon class="mr-1.5 h-4 w-4" />
          {{ $t('Institucijos') }}
          <Badge v-if="taskStats?.byType" variant="secondary" class="ml-1.5 tabular-nums">
            {{ taskStats.byType.institutions }}
          </Badge>
        </Button>
        <Button
          :variant="filters.taskable_type === ModelEnum.RESERVATION ? 'default' : 'outline'"
          size="sm"
          @click="updateFilter('taskable_type', ModelEnum.RESERVATION)"
        >
          <PackageIcon class="mr-1.5 h-4 w-4" />
          {{ $t('Rezervacijos') }}
          <Badge v-if="taskStats?.byType" variant="secondary" class="ml-1.5 tabular-nums">
            {{ taskStats.byType.reservations }}
          </Badge>
        </Button>
      </div>

      <!-- Tenant filter -->
      <div v-if="permissibleTenants.length > 1" class="flex items-center gap-2">
        <DataTableFilter
          v-model:value="selectedTenantIds"
          :options="tenantOptions"
          multiple
          @update:value="handleTenantFilterChange"
        >
          {{ $t('Padalinys') }}
        </DataTableFilter>
      </div>
    </div>

    <TaskStatsCards v-if="taskStats" :task-stats />

    <!-- Task manager with table -->
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <div class="p-4 sm:p-6">
        <TaskManager
          :tasks="tasks.data"
          :task-stats
          :current-filter="currentFilter"
          :total-count="tasks.meta.total"
          server-side-filter
          server-paginated
          @filter-change="handleCompletionFilterChange"
          @open-meeting-modal="handleOpenMeetingModal"
          @open-check-in-dialog="handleOpenCheckInDialog"
          @open-task-detail="handleOpenTaskDetail"
        />
      </div>

      <TaskPagination
        :current-page="tasks.meta.current_page"
        :last-page="tasks.meta.last_page"
        :total="tasks.meta.total"
        :from="tasks.meta.from"
        :to="tasks.meta.to"
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
      @schedule-meeting="handleScheduleMeetingFromDetail"
      @report-no-meeting="handleReportNoMeetingFromDetail"
    />
  </AdminContentPage>
</template>

<script setup lang="ts">
import { ModelEnum } from '@/Types/enums';
import { router } from '@inertiajs/vue3';
import { ref, computed, defineAsyncComponent } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Building as BuildingIcon, Package as PackageIcon } from 'lucide-vue-next';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useActionWindow } from '@/Composables/useActionWindow';
import type { TaskDisplayData, TaskStats } from '@/Composables/useTaskPresentation';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import TaskStatsCards from '@/Features/Admin/TaskManager/TaskStatsCards.vue';
import TaskPagination from '@/Features/Admin/TaskManager/TaskPagination.vue';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { TaskIcon } from '@/Components/icons';

// Lazy load modals
const AddCheckInDialog = defineAsyncComponent(() => import('@/Components/Institutions/AddCheckInDialog.vue'));
const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));

/** The summary page's own stats add a per-taskable-type breakdown to the shared counts. */
interface SummaryTaskStats extends TaskStats {
  byType: {
    institutions: number;
    reservations: number;
  };
}

interface PaginationMeta {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  from: number | null;
  to: number | null;
}

interface Filters {
  taskable_type?: string | null;
  completion?: string | null;
  tenant_ids?: number[];
}

interface PermissibleTenant {
  id: number;
  shortname: string;
}

const props = defineProps<{
  tasks: {
    data: TaskDisplayData[];
    meta: PaginationMeta;
  };
  taskStats?: SummaryTaskStats;
  filters: Filters;
  permissibleTenants: PermissibleTenant[];
}>();

// Local state
const selectedTenantIds = ref<number[]>(props.filters.tenant_ids || []);

// Tenant options for dropdown
const tenantOptions = computed(() =>
  props.permissibleTenants.map(tenant => ({
    label: tenant.shortname,
    value: tenant.id,
  })),
);

/**
 * The backend speaks `pending`/`completed`/absent; TaskManager speaks
 * `incomplete`/`completed`/`all`. Translate rather than filtering client-side, which would only
 * ever have filtered the page in hand.
 */
const currentFilter = computed<'all' | 'completed' | 'incomplete'>(() => {
  switch (props.filters.completion) {
    case 'all':
      return 'all';
    case 'completed':
      return 'completed';
    default:
      return 'incomplete';
  }
});

type FilterValue = string | number | number[] | null | undefined;

const visitWithFilters = (overrides: Record<string, FilterValue>) => {
  const query: Record<string, FilterValue> = { ...props.filters, page: 1, ...overrides };

  Object.keys(query).forEach((key) => {
    const value = query[key];
    if (value === null || value === undefined || (Array.isArray(value) && value.length === 0)) {
      delete query[key];
    }
  });

  router.visit(route('tasks.summary', query), {
    preserveState: true,
    preserveScroll: true,
  });
};

const updateFilter = (key: keyof Filters, value: string | null) => {
  visitWithFilters({ [key]: value });
};

const COMPLETION_QUERY_VALUE = {
  all: 'all',
  completed: 'completed',
  incomplete: 'pending',
} as const;

const handleCompletionFilterChange = (status: 'all' | 'completed' | 'incomplete') => {
  visitWithFilters({ completion: COMPLETION_QUERY_VALUE[status] });
};

const handleTenantFilterChange = (tenantIds: number[]) => {
  visitWithFilters({ tenant_ids: tenantIds });
};

const goToPage = (page: number) => {
  router.visit(route('tasks.summary', { ...props.filters, page }), {
    preserveState: true,
    preserveScroll: true,
  });
};

const actionWindow = useActionWindow();

// Modal state
const showCheckInDialog = ref(false);
const showTaskDetail = ref(false);
const selectedCheckInTask = ref<TaskDisplayData | null>(null);
const selectedDetailTask = ref<TaskDisplayData | null>(null);

const checkInStartDate = computed(() => new Date());
const checkInEndDate = computed(() =>
  selectedCheckInTask.value?.due_date
    ? new Date(selectedCheckInTask.value.due_date)
    : new Date(Date.now() + 14 * 24 * 60 * 60 * 1000),
);

const handleOpenMeetingModal = (task: TaskDisplayData) => {
  if (!task.taskable) {
    return;
  }

  actionWindow.open({
    flow: 'meeting.create',
    institution: { id: task.taskable_id, name: task.taskable.name } as App.Entities.Institution,
  });
};

const handleOpenCheckInDialog = (task: TaskDisplayData) => {
  selectedCheckInTask.value = task;
  showCheckInDialog.value = true;
};

const closeCheckInDialog = () => {
  showCheckInDialog.value = false;
  selectedCheckInTask.value = null;
};

const handleOpenTaskDetail = (task: TaskDisplayData) => {
  selectedDetailTask.value = task;
  showTaskDetail.value = true;
};

const closeTaskDetail = () => {
  showTaskDetail.value = false;
  selectedDetailTask.value = null;
};

const handleScheduleMeetingFromDetail = () => {
  if (selectedDetailTask.value) {
    const task = selectedDetailTask.value;
    closeTaskDetail();
    handleOpenMeetingModal(task);
  }
};

const handleReportNoMeetingFromDetail = () => {
  if (selectedDetailTask.value) {
    const task = selectedDetailTask.value;
    closeTaskDetail();
    handleOpenCheckInDialog(task);
  }
};

// Generate breadcrumbs
usePageBreadcrumbs([
  { label: $t('tasks.summary.title'), icon: TaskIcon },
]);
</script>
