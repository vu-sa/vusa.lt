<template>
  <Dialog :open @update:open="handleOpenChange">
    <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
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
          <Badge v-if="task.is_overdue" variant="rose" class="gap-1">
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

        <!-- Assigned users. A meeting task can carry the whole institution, so these are
             avatars with the names behind a hover card rather than a pill per person — a
             20-name list used to push the dialog past the height of the screen. -->
        <div v-if="task.users?.length" class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
          <div class="mb-2 flex items-center gap-2">
            <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
              {{ $t('tasks.assigned_to') }}
            </h4>
            <Badge variant="secondary" class="tabular-nums">
              {{ task.users.length }}
            </Badge>
          </div>
          <UsersAvatarGroup :users="task.users" :size="28" :max="8" />
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
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import {
  isPeriodicityGapTask as isPeriodicityGap,
  type TaskDisplayData,
} from '@/Composables/useTaskPresentation';

const props = defineProps<{
  open: boolean;
  task: TaskDisplayData;
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

// This compared against 'PeriodicityGap', a value the ActionType enum never emits, so the
// dialog's action buttons were unreachable.
const isPeriodicityGapTask = computed(() => isPeriodicityGap(props.task));

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
