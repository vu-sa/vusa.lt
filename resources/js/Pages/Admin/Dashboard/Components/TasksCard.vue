<template>
  <Card :class="dashboardCardClasses" role="region" :aria-label="$t('Tavo užduotys')">
    <!-- Status indicator - subtle amber accent when tasks exist -->
    <div :class="statusIndicatorClasses" aria-hidden="true" />

    <CardHeader class="pb-2 relative z-10">
      <div class="flex items-center justify-between">
        <CardTitle class="flex items-center gap-2 text-base">
          <ClipboardCheckIcon :class="iconClasses" aria-hidden="true" />
          {{ $t('Užduotys') }}
        </CardTitle>
        <!-- Compact stats -->
        <div class="flex items-center gap-2">
          <span class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">
            {{ taskStats.total }}
          </span>
          <div v-if="taskStats.overdue > 0" class="flex items-center gap-1">
            <span :class="['h-2 w-2 rounded-full', cardAccentColors.amber.dot]" />
            <span :class="['text-xs', cardAccentColors.amber.text]">{{ taskStats.overdue }}</span>
          </div>
        </div>
      </div>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 pt-0">
      <!-- Scrollable task list - show up to 10 tasks -->
      <ScrollArea v-if="upcomingTasks.length > 0" class="max-h-[320px]">
        <div class="space-y-0.5 pr-3">
          <div
            v-for="task in displayedTasks"
            :key="task.id"
            class="group flex items-center gap-2 py-2 px-2 -mx-2 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-700/50 transition-colors"
          >
            <!-- Checkbox for completion -->
            <Checkbox
              :model-value="false"
              :disabled="isUpdating === task.id"
              class="shrink-0 transition-all duration-200 hover:scale-110 dark:border-zinc-500"
              @update:model-value="() => completeTask(task)"
            />
            
            <!-- Task content - clickable to navigate -->
            <div 
              class="flex-1 min-w-0 cursor-pointer"
              @click="navigateToTasks"
            >
              <span class="text-sm truncate block text-zinc-700 dark:text-zinc-300">
                {{ task.name }}
              </span>
            </div>
            
            <!-- Due date - compact -->
            <span :class="[
              'text-xs shrink-0',
              task.is_overdue 
                ? 'text-amber-600 dark:text-amber-400 font-medium' 
                : 'text-zinc-400 dark:text-zinc-500'
            ]">
              {{ formatTaskDate(task.due_date) }}
            </span>

            <!-- Actions dropdown -->
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button 
                  variant="ghost" 
                  size="icon" 
                  class="h-6 w-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
                  :disabled="isUpdating === task.id"
                >
                  <MoreHorizontalIcon class="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem 
                  class="text-destructive focus:text-destructive cursor-pointer"
                  @click="deleteTask(task)"
                >
                  <TrashIcon class="mr-2 h-4 w-4" />
                  <span>{{ $t('Delete') }}</span>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </ScrollArea>

      <!-- Empty state -->
      <div v-else class="text-center py-6">
        <div class="text-2xl mb-2">✨</div>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
          {{ $t('Viskas atlikta!') }}
        </p>
      </div>

      <!-- Footer with link -->
      <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
        <Link 
          :href="route('userTasks')" 
          class="flex items-center justify-between text-sm text-zinc-600 dark:text-zinc-400 hover:text-primary transition-colors"
        >
          <span>{{ $t('Visos užduotys') }}</span>
          <ChevronRightIcon class="h-4 w-4" />
        </Link>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Link, router, usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { formatDistanceToNow, parseISO, isToday, isTomorrow } from "date-fns";
import { lt, enUS } from "date-fns/locale";
import { computed, ref } from "vue";
import { toast } from "vue-sonner";

import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Checkbox } from "@/Components/ui/checkbox";
import { Button } from "@/Components/ui/button";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from "@/Components/ui/dropdown-menu";
import { 
  ClipboardCheck as ClipboardCheckIcon, 
  ChevronRight as ChevronRightIcon,
  MoreHorizontal as MoreHorizontalIcon,
  Trash as TrashIcon,
  Check as CheckIcon
} from "lucide-vue-next";
import { dashboardCardClasses, cardAccentColors } from '@/Composables/useDashboardCardStyles';

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
}

const props = defineProps<{
  taskStats: TaskStats;
  upcomingTasks: UpcomingTask[];
}>();

// Track which task is being updated
const isUpdating = ref<string | null>(null);

// Computed classes based on task stats using shared accent colors
const statusIndicatorClasses = computed(() => {
  const base = 'absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45'
  if (props.taskStats.overdue > 0) {
    return `${base} ${cardAccentColors.amber.statusIndicatorActive}`
  }
  if (props.taskStats.total > 0) {
    return `${base} ${cardAccentColors.amber.statusIndicator}`
  }
  return `${base} bg-zinc-200 dark:bg-zinc-700`
})

const iconClasses = computed(() => {
  return `h-5 w-5 ${props.taskStats.total > 0 ? cardAccentColors.amber.icon : cardAccentColors.amber.iconMuted}`
})

// Locale for date formatting
const dateLocale = computed(() => usePage().props.app.locale === 'lt' ? lt : enUS);

// Display up to 10 tasks
const displayedTasks = computed(() => props.upcomingTasks.slice(0, 10));

// Navigate to tasks page
const navigateToTasks = () => {
  router.visit(route('userTasks'));
};

// Complete a task
const completeTask = (task: UpcomingTask) => {
  if (isUpdating.value) return;
  isUpdating.value = task.id;

  router.post(
    route("tasks.updateCompletionStatus", task.id),
    { completed: true },
    {
      preserveScroll: true,
      preserveState: false, // Refresh to update task list
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
    preserveState: false, // Refresh to update task list
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

// Format task due date - very compact
const formatTaskDate = (dateStr: string | null) => {
  if (!dateStr) return '';
  try {
    const date = parseISO(dateStr);
    if (isToday(date)) return $t('Šiandien');
    if (isTomorrow(date)) return $t('Rytoj');
    return formatDistanceToNow(date, { addSuffix: false, locale: dateLocale.value });
  } catch {
    return '';
  }
};
</script>
