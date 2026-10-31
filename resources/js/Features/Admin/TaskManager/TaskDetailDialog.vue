<template>
  <Dialog :open @update:open="handleOpenChange">
    <DialogContent
      :class="[
        'max-h-[85dvh] gap-0 overflow-hidden p-0 sm:max-w-xl',
        'max-sm:top-auto max-sm:bottom-0 max-sm:left-0 max-sm:max-w-full max-sm:translate-x-0 max-sm:translate-y-0 max-sm:border-x-0 max-sm:border-b-0',
      ]"
    >
      <DialogTitle class="sr-only">
        {{ task.name }}
      </DialogTitle>
      <DialogDescription class="sr-only">
        {{ task.taskable?.name }}
      </DialogDescription>

      <TaskPreview class="max-h-[85dvh]" :task :actions="paneActions" @select="select" />
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { getTaskActions, type TaskAction, type TaskActionKey } from './taskActions';
import TaskPreview from './TaskPreview.vue';

import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/Components/ui/dialog';
import type { TaskDisplayData } from '@/Composables/useTaskPresentation';

const props = defineProps<{
  open: boolean;
  task: TaskDisplayData;
  /** Everything the page can run. Without it the dialog offers only what needs no page wiring. */
  actions?: TaskAction[];
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'report'): void;
  (e: 'action', key: TaskActionKey): void;
}>();

const paneActions = computed(() => props.actions ?? getTaskActions(props.task).filter(action => action.key === 'report' || action.key === 'agenda'));

const handleOpenChange = (value: boolean) => {
  if (!value) {
    emit('close');
  }
};

function select(key: TaskActionKey): void {
  if (key === 'report') {
    emit('report');
  }
  else {
    emit('action', key);
  }
}
</script>
