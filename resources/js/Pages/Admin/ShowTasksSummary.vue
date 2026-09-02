<template>
  <AdminContentPage :title="$t('tasks.summary.title')">
    <!-- Filters row -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
      <!-- Type filter: independently toggleable, not mutually exclusive — a periodicity-gap
           task (taskable=institution) and an agenda task (taskable=meeting) are both "about a
           meeting", so a caller wanting one often wants both. -->
      <div class="flex flex-wrap items-center gap-2">
        <Button
          :variant="selectedTaskableTypes.length === 0 ? 'default' : 'outline'"
          size="sm"
          @click="clearTaskableTypes"
        >
          {{ $t('Visi') }}
        </Button>
        <Button
          v-for="option in taskableTypeOptions"
          :key="option.value"
          :variant="selectedTaskableTypes.includes(option.value) ? 'default' : 'outline'"
          size="sm"
          @click="toggleTaskableType(option.value)"
        >
          <component :is="option.icon" class="mr-1.5 h-4 w-4" />
          {{ option.label }}
          <Badge v-if="taskStats?.byType" variant="secondary" class="ml-1.5 tabular-nums">
            {{ taskStats.byType[option.value] }}
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
          @open-meeting-modal="openMeetingModal"
          @open-check-in-dialog="openCheckInDialog"
          @open-task-detail="openTaskDetail"
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
      @schedule-meeting="scheduleMeetingFromDetail"
      @report-no-meeting="reportNoMeetingFromDetail"
    />
  </AdminContentPage>
</template>

<script setup lang="ts">
import { ModelEnum } from '@/Types/enums';
import { router } from '@inertiajs/vue3';
import { ref, computed, defineAsyncComponent } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Building as BuildingIcon, Calendar as CalendarIcon, Package as PackageIcon } from 'lucide-vue-next';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
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
    institution: number;
    meeting: number;
    reservation: number;
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
  taskable_type?: string[] | null;
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

const taskableTypeOptions = [
  { value: ModelEnum.INSTITUTION, label: $t('Institucijos'), icon: BuildingIcon },
  { value: ModelEnum.MEETING, label: $t('Posėdžiai'), icon: CalendarIcon },
  { value: ModelEnum.RESERVATION, label: $t('Rezervacijos'), icon: PackageIcon },
] as const;

const selectedTaskableTypes = computed<string[]>(() => props.filters.taskable_type ?? []);

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

type FilterValue = string | number | string[] | number[] | null | undefined;

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

const toggleTaskableType = (type: string) => {
  const current = selectedTaskableTypes.value;
  const next = current.includes(type) ? current.filter(t => t !== type) : [...current, type];
  visitWithFilters({ taskable_type: next });
};

const clearTaskableTypes = () => {
  visitWithFilters({ taskable_type: null });
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
  { label: $t('tasks.summary.title'), icon: TaskIcon },
]);
</script>
