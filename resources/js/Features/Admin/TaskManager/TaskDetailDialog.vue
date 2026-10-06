<template>
  <Dialog :open @update:open="handleOpenChange">
    <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <component :is="taskIcon" class="h-5 w-5 text-muted-foreground" />
          {{ task.name }}
        </DialogTitle>
        <DialogDescription v-if="task.taskable?.name">
          {{ task.taskable.name }}
        </DialogDescription>
      </DialogHeader>

      <TaskDetails :task @report="emit('report')" />

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
  CalendarPlus as CalendarPlusIcon,
  ClipboardList as ClipboardListIcon,
} from 'lucide-vue-next';

import TaskDetails from './TaskDetails.vue';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { isPeriodicityGapTask, type TaskDisplayData } from '@/Composables/useTaskPresentation';

const props = defineProps<{
  open: boolean;
  task: TaskDisplayData;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'report'): void;
}>();

const handleOpenChange = (value: boolean) => {
  if (!value) {
    emit('close');
  }
};

const taskIcon = computed(() => (isPeriodicityGapTask(props.task) ? CalendarPlusIcon : ClipboardListIcon));
</script>
