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
            {{ taskStats.byType.meetings + taskStats.byType.reservations }}
          </Badge>
        </Button>
        <Button
          :variant="filters.taskable_type === 'App\\Models\\Meeting' ? 'default' : 'outline'"
          size="sm"
          @click="updateFilter('taskable_type', 'App\\Models\\Meeting')"
        >
          <CalendarIcon class="mr-1.5 h-4 w-4" />
          {{ $t('Posėdžiai') }}
          <Badge v-if="taskStats?.byType" variant="secondary" class="ml-1.5 tabular-nums">
            {{ taskStats.byType.meetings }}
          </Badge>
        </Button>
        <Button
          :variant="filters.taskable_type === 'App\\Models\\Reservation' ? 'default' : 'outline'"
          size="sm"
          @click="updateFilter('taskable_type', 'App\\Models\\Reservation')"
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

    <!-- Stats overview cards -->
    <div v-if="taskStats" class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Total pending -->
      <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
            <ClipboardListIcon class="h-5 w-5 text-zinc-600 dark:text-zinc-400" />
          </div>
          <div>
            <p class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
{{ taskStats.total }}
</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
{{ $t('tasks.stats.pending') }}
</p>
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
            <p class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
{{ taskStats.overdue }}
</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
{{ $t('overdue') }}
</p>
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
            <p class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
{{ taskStats.autoCompleting }}
</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
{{ $t('tasks.stats.auto_completing') }}
</p>
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
            <p class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
{{ taskStats.completed }}
</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
{{ $t('completed') }}
</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Task manager with table -->
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <div class="p-4 sm:p-6">
        <TaskManager :tasks="tasks.data" :task-stats />
      </div>

      <!-- Pagination -->
      <div v-if="tasks.meta.last_page > 1" class="flex items-center justify-between border-t border-zinc-200 px-4 py-3 dark:border-zinc-800 sm:px-6">
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
          {{ tasks.meta.from }} - {{ tasks.meta.to }} / {{ tasks.meta.total }}
        </div>
        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            :disabled="tasks.meta.current_page === 1"
            @click="goToPage(tasks.meta.current_page - 1)"
          >
            <ChevronLeftIcon class="h-4 w-4" />
          </Button>
          <span class="text-sm tabular-nums text-zinc-600 dark:text-zinc-400">
            {{ tasks.meta.current_page }} / {{ tasks.meta.last_page }}
          </span>
          <Button
            variant="outline"
            size="sm"
            :disabled="tasks.meta.current_page === tasks.meta.last_page"
            @click="goToPage(tasks.meta.current_page + 1)"
          >
            <ChevronRightIcon class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { 
  ClipboardList as ClipboardListIcon,
  AlertCircle as AlertCircleIcon,
  RotateCw as RotateCwIcon,
  CheckCircle as CheckCircleIcon,
  Calendar as CalendarIcon,
  Package as PackageIcon,
  ChevronLeft as ChevronLeftIcon,
  ChevronRight as ChevronRightIcon,
} from "lucide-vue-next";

import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import { usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import Icons from "@/Types/Icons/regular";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import type { TaskProgress, TaskActionType } from "@/Types/TaskTypes";

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
  byType: {
    meetings: number;
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
    data: TaskWithDetails[];
    meta: PaginationMeta;
  };
  taskStats?: TaskStats;
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
  }))
);

// Update filter and reload page
const updateFilter = (key: keyof Filters, value: string | null) => {
  const newFilters = {
    ...props.filters,
    page: 1, // Reset to first page when filter changes
  } as Record<string, string | number | number[] | null | undefined>;
  
  if (value === null) {
    delete newFilters[key];
  } else {
    newFilters[key] = value;
  }

  router.visit(route('tasks.summary', newFilters), {
    preserveState: true,
    preserveScroll: true,
  });
};

// Handle tenant filter change
const handleTenantFilterChange = (tenantIds: number[]) => {
  router.visit(route('tasks.summary', {
    ...props.filters,
    tenant_ids: tenantIds.length > 0 ? tenantIds : undefined,
    page: 1,
  }), {
    preserveState: true,
    preserveScroll: true,
  });
};

// Pagination
const goToPage = (page: number) => {
  router.visit(route('tasks.summary', {
    ...props.filters,
    page,
  }), {
    preserveState: true,
    preserveScroll: true,
  });
};

// Generate breadcrumbs
usePageBreadcrumbs([
  { label: $t('tasks.summary.title'), icon: Icons.TASK }
]);
</script>
