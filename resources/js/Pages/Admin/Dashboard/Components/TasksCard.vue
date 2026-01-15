<template>
  <Card :class="dashboardCardClasses" role="region" :aria-label="$t('Tavo užduotys')">
    <!-- Status indicator - accent based on urgency -->
    <div :class="statusIndicatorClasses" aria-hidden="true" />

    <CardHeader class="relative z-10 pb-3">
      <div class="flex items-center justify-between">
        <CardTitle class="flex items-center gap-2 text-base font-semibold">
          <div :class="['rounded-lg p-1.5', headerIconBgClass]">
            <ClipboardCheckIcon :class="['h-4 w-4', headerIconClass]" aria-hidden="true" />
          </div>
          {{ $t('Užduotys') }}
        </CardTitle>
        
        <!-- Stats badges -->
        <div class="flex items-center gap-2">
          <Badge 
            variant="secondary" 
            class="text-sm font-bold tabular-nums"
          >
            {{ taskStats.total }}
          </Badge>
          <Badge 
            v-if="taskStats.overdue > 0" 
            variant="destructive"
            class="text-xs font-medium tabular-nums"
          >
            {{ taskStats.overdue }} {{ $t('overdue') }}
          </Badge>
          <Badge 
            v-else-if="taskStats.dueSoon > 0" 
            class="bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 text-xs font-medium tabular-nums"
          >
            {{ taskStats.dueSoon }} {{ $t('soon') }}
          </Badge>
        </div>
      </div>
    </CardHeader>

    <CardContent class="relative z-10 flex-1 pt-0">
      <!-- Task list -->
      <ScrollArea v-if="displayedTasks.length > 0" class="max-h-[340px]">
        <div class="space-y-1 pr-2">
          <TaskItem
            v-for="task in displayedTasks"
            :key="task.id"
            :id="task.id"
            :name="task.name"
            :due-date="task.due_date"
            :is-overdue="task.is_overdue"
            :action-type="task.action_type"
            :progress="task.progress"
            :can-be-manually-completed="task.can_be_manually_completed"
            :is-updating="isUpdating === task.id"
            @complete="completeTask(task)"
            @delete="deleteTask(task)"
          />
        </div>
      </ScrollArea>

      <!-- Empty state - celebratory -->
      <div v-else class="flex flex-col items-center justify-center py-8 text-center">
        <div class="mb-3 rounded-full bg-zinc-100 p-3 dark:bg-zinc-800">
          <CheckCircleIcon class="h-6 w-6 text-zinc-500 dark:text-zinc-400" />
        </div>
        <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
          {{ $t('Viskas atlikta!') }}
        </h3>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
          {{ $t('Šiuo metu neturite aktyvių užduočių') }}
        </p>
      </div>

      <!-- Footer with link -->
      <div class="mt-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
        <Link 
          :href="route('userTasks')" 
          class="group flex items-center justify-between rounded-md px-2 py-1.5 text-sm text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
        >
          <span>{{ $t('Visos užduotys') }}</span>
          <ChevronRightIcon class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
        </Link>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref } from "vue";
import { toast } from "vue-sonner";

import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { 
  ClipboardCheck as ClipboardCheckIcon, 
  ChevronRight as ChevronRightIcon,
  CheckCircle as CheckCircleIcon,
} from "lucide-vue-next";
import { dashboardCardClasses, cardAccentColors } from '@/Composables/useDashboardCardStyles';
import TaskItem from "@/Components/Tasks/TaskItem.vue";
import type { TaskProgress, TaskActionType } from "@/Types/TaskTypes";

interface TaskStats {
  total: number;
  overdue: number;
  dueSoon: number;
}

interface UpcomingTask {
  id: string;
  name: string;
  due_date: string | null;
  is_overdue: boolean;
  taskable_type: string;
  taskable_id: string;
  action_type?: TaskActionType | string | null;
  progress?: TaskProgress | null;
  can_be_manually_completed?: boolean;
}

const props = defineProps<{
  taskStats: TaskStats;
  upcomingTasks: UpcomingTask[];
}>();

// Track which task is being updated
const isUpdating = ref<string | null>(null);

// Computed classes based on task stats
const statusIndicatorClasses = computed(() => {
  const base = 'absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45'
  if (props.taskStats.overdue > 0) {
    return `${base} bg-red-300/40 dark:bg-red-800/25`
  }
  if (props.taskStats.dueSoon > 0) {
    return `${base} ${cardAccentColors.amber.statusIndicatorActive}`
  }
  if (props.taskStats.total > 0) {
    return `${base} ${cardAccentColors.amber.statusIndicator}`
  }
  return `${base} bg-emerald-300/40 dark:bg-emerald-800/25`
})

const headerIconBgClass = computed(() => {
  if (props.taskStats.overdue > 0) {
    return 'bg-red-100 dark:bg-red-900/30'
  }
  if (props.taskStats.total > 0) {
    return 'bg-amber-100 dark:bg-amber-900/30'
  }
  return 'bg-emerald-100 dark:bg-emerald-900/30'
})

const headerIconClass = computed(() => {
  if (props.taskStats.overdue > 0) {
    return 'text-red-600 dark:text-red-400'
  }
  if (props.taskStats.total > 0) {
    return 'text-amber-600 dark:text-amber-400'
  }
  return 'text-emerald-600 dark:text-emerald-400'
})

// Display up to 10 tasks
const displayedTasks = computed(() => props.upcomingTasks.slice(0, 10));

// Complete a task
const completeTask = (task: UpcomingTask) => {
  // Don't allow completing auto-completing tasks
  if (task.can_be_manually_completed === false) {
    toast.info($t("This task completes automatically"), {
      description: $t("You cannot manually complete this task"),
    });
    return;
  }

  if (isUpdating.value) return;
  isUpdating.value = task.id;

  router.post(
    route("tasks.updateCompletionStatus", task.id),
    { completed: true },
    {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        isUpdating.value = null;
        toast.success($t("Task marked as completed"), {
          description: task.name,
        });
      },
      onError: () => {
        isUpdating.value = null;
        toast.error($t("Failed to update task status"), {
          description: $t("Please try again"),
        });
      },
    }
  );
};

// Delete a task
const deleteTask = (task: UpcomingTask) => {
  if (isUpdating.value) return;
  isUpdating.value = task.id;

  router.delete(route("tasks.destroy", task.id), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      isUpdating.value = null;
      toast.success($t("Task deleted successfully"), {
        description: task.name,
      });
    },
    onError: () => {
      isUpdating.value = null;
      toast.error($t("Failed to delete task"), {
        description: $t("Please try again"),
      });
    },
  });
};
</script>
