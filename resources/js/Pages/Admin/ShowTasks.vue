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
      <TaskManager :tasks="tasks" :task-stats="taskStats" />
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
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

// Generate breadcrumbs
usePageBreadcrumbs([
  { label: $t('Užduotys'), icon: Icons.TASK }
]);
</script>
