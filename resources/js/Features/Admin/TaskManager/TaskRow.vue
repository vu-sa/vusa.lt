<template>
  <CollectionRow
    :actions="hideActions ? [] : (actions ?? getTaskRowActions(task))"
    :spotlight
    :class="['px-3 py-2.5 sm:px-4', task.completed_at && 'opacity-60']"
    data-slot="task-row"
    @select="key => emit('action', key as TaskActionKey)"
  >
    <template #lead>
      <TaskCompletionControl :task :loading @toggle="emit('action', 'complete')" />
    </template>

    <CollectionPrimaryCell :title-lines="2" clickable @open="emit('open')">
      <span :class="task.completed_at && 'line-through'">{{ task.name }}</span>
      <template #sub>
        {{ subject }}<template v-if="task.cancelled_at">
          · {{ $t('tasks.cancelled') }}
        </template><template v-else-if="task.due_date">
          · <span :class="getDueDateUrgencyClasses(task)">{{ formatTaskDueDate(task.due_date, dateLocale) }}</span>
        </template>
      </template>
    </CollectionPrimaryCell>
  </CollectionRow>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { getTaskRowActions, type TaskActionKey } from './taskActions';
import TaskCompletionControl from './TaskCompletionControl.vue';

import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import { useDateLocale } from '@/Composables/useDateLocale';
import {
  formatTaskDueDate,
  getDueDateUrgencyClasses,
  isOrphanedTask,
  type TaskDisplayData,
} from '@/Composables/useTaskPresentation';

/**
 * One task as a list row: the collection's rows view and every embedded task list on a record
 * page (meeting, institution, user) draw the same thing.
 */
const props = defineProps<{
  task: TaskDisplayData;
  actions?: CollectionRowAction[];
  spotlight?: boolean;
  loading?: boolean;
  /** Beside the preview pane the row only selects; the pane carries the actions. */
  hideActions?: boolean;
}>();

const emit = defineEmits<{
  open: [];
  action: [key: TaskActionKey];
}>();

const dateLocale = useDateLocale();

const subject = computed(() => (isOrphanedTask(props.task)
  ? $t('tasks.orphaned')
  : props.task.taskable?.name ?? props.task.taskable_type));
</script>
