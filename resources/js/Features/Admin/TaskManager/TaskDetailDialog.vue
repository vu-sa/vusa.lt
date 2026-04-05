<template>
  <Dialog :open @update:open="handleOpenChange">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <component :is="taskIcon" class="h-5 w-5 text-zinc-500" />
          {{ task.name }}
        </DialogTitle>
        <DialogDescription v-if="task.taskable?.name">
          {{ task.taskable.name }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <!-- Task status info -->
        <div class="flex flex-wrap items-center gap-2 text-sm">
          <Badge v-if="task.is_overdue" variant="destructive" class="gap-1">
            <AlertCircleIcon class="h-3 w-3" />
            {{ $t('overdue') }}
          </Badge>
          <Badge v-if="!task.can_be_manually_completed" variant="secondary" class="gap-1">
            <RotateCwIcon class="h-3 w-3" />
            {{ $t('tasks.auto_completing') }}
          </Badge>
          <span v-if="task.due_date" class="text-zinc-500">
            {{ $t('tasks.due') }}: {{ formatDate(task.due_date) }}
          </span>
        </div>

        <!-- Task description / instructions -->
        <div v-if="task.description" class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
          <h4 class="mb-2 flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            <InfoIcon class="h-4 w-4" />
            {{ $t('tasks.instructions') }}
          </h4>
          <p class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-line">
            {{ task.description }}
          </p>
        </div>

        <!-- Action buttons for PeriodicityGap tasks -->
        <div v-if="isPeriodicityGapTask" class="space-y-3">
          <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $t('tasks.available_actions') }}
          </h4>

          <div class="grid gap-2 sm:grid-cols-2">
            <!-- Schedule meeting button -->
            <Button
              variant="default"
              class="h-auto flex-col gap-1 py-3"
              @click="emit('scheduleMeeting')"
            >
              <CalendarPlusIcon class="h-5 w-5" />
              <span>{{ $t('tasks.periodicity_gap.schedule_meeting') }}</span>
            </Button>

            <!-- Report check-in button -->
            <Button
              variant="outline"
              class="h-auto flex-col gap-1 py-3"
              @click="emit('reportNoMeeting')"
            >
              <CalendarOffIcon class="h-5 w-5" />
              <span>{{ $t('tasks.periodicity_gap.report_no_meeting') }}</span>
            </Button>
          </div>
        </div>

        <!-- Assigned users -->
        <div v-if="task.users && task.users.length > 0" class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
          <h4 class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $t('tasks.assigned_to') }}
          </h4>
          <div class="flex flex-wrap gap-2">
            <div
              v-for="user in task.users"
              :key="user.id"
              class="flex items-center gap-2 rounded-full bg-zinc-100 px-3 py-1 text-sm dark:bg-zinc-800"
            >
              <div class="h-5 w-5 rounded-full bg-zinc-300 dark:bg-zinc-600" />
              <span>{{ user.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter class="mt-4">
        <Button variant="outline" @click="emit('close')">
          {{ $t('Uždaryti') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  AlertCircle as AlertCircleIcon,
  CalendarPlus as CalendarPlusIcon,
  CalendarOff as CalendarOffIcon,
  Info as InfoIcon,
  RotateCw as RotateCwIcon,
  ClipboardList as ClipboardListIcon,
} from 'lucide-vue-next';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import type { TaskProgress, TaskActionType } from '@/Types/TaskTypes';

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

const props = defineProps<{
  open: boolean;
  task: TaskWithDetails;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'scheduleMeeting'): void;
  (e: 'reportNoMeeting'): void;
}>();

const handleOpenChange = (value: boolean) => {
  if (!value) {
    emit('close');
  }
};

const isPeriodicityGapTask = computed(() => {
  return props.task.action_type === 'PeriodicityGap';
});

const taskIcon = computed(() => {
  if (isPeriodicityGapTask.value) {
    return CalendarPlusIcon;
  }
  return ClipboardListIcon;
});

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};
</script>
