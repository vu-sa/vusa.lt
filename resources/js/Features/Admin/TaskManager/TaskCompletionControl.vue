<template>
  <div class="flex size-9 shrink-0 items-center justify-center pointer-coarse:size-11" data-slot="task-completion-control">
    <RotateCw v-if="loading" class="size-4 animate-spin text-muted-foreground" aria-hidden="true" />

    <!-- The system closes an automatic task itself: show how far it has got, not a checkbox. -->
    <span
      v-else-if="task.can_be_manually_completed === false && task.progress"
      class="relative flex size-6 items-center justify-center"
      :title="`${task.progress.current} / ${task.progress.total} ${$t('completed')}`"
    >
      <svg class="size-6 -rotate-90" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" class="text-muted" />
        <circle
          cx="12"
          cy="12"
          r="10"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          :stroke-dasharray="`${(task.progress.percentage / 100) * 62.8} 62.8`"
          :class="getTaskProgressStrokeClass(task.action_type)"
        />
      </svg>
      <span class="sr-only">{{ task.progress.current }} / {{ task.progress.total }}</span>
    </span>

    <span
      v-else-if="task.can_be_manually_completed === false"
      :class="['flex size-6 items-center justify-center border border-border', getTaskActionBadgeClasses(task.action_type)]"
      :title="$t('This task completes automatically')"
    >
      <component :is="getTaskActionIcon(task.action_type)" class="size-3.5" aria-hidden="true" />
      <span class="sr-only">{{ $t('This task completes automatically') }}</span>
    </span>

    <Checkbox
      v-else
      :model-value="Boolean(task.completed_at)"
      :aria-label="task.completed_at ? $t('tasks.collection.reopen') : $t('tasks.collection.complete')"
      @update:model-value="emit('toggle')"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { RotateCw } from 'lucide-vue-next';

import { Checkbox } from '@/Components/ui/checkbox';
import {
  getTaskActionBadgeClasses,
  getTaskActionIcon,
  getTaskProgressStrokeClass,
  type TaskDisplayData,
} from '@/Composables/useTaskPresentation';

defineProps<{
  task: TaskDisplayData;
  loading?: boolean;
}>();

const emit = defineEmits<{
  toggle: [];
}>();
</script>
