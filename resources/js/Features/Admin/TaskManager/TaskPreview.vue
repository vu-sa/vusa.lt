<template>
  <PreviewPane entity-type="task" :eyebrow="getTaskActionLabel(task.action_type)" :title="task.name">
    <template #title>
      <span :class="task.completed_at && 'text-muted-foreground line-through'">{{ task.name }}</span>
    </template>

    <template #subtitle>
      <Link v-if="taskableUrl" :href="taskableUrl" class="underline-offset-4 hover:text-brand hover:underline">
        {{ subject }}
      </Link>
      <template v-else>
        {{ subject }}
      </template>
    </template>

    <template v-if="$slots.actions" #actions>
      <slot name="actions" />
    </template>

    <TaskDetails :task hide-actions />
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import TaskDetails from './TaskDetails.vue';

import { PreviewPane } from '@/Components/Patterns';
import { getTaskActionLabel, getTaskableUrl, isOrphanedTask, type TaskDisplayData } from '@/Composables/useTaskPresentation';

const props = defineProps<{
  task: TaskDisplayData;
}>();

const taskableUrl = computed(() => getTaskableUrl(props.task));
const subject = computed(() => (isOrphanedTask(props.task) ? $t('tasks.orphaned') : props.task.taskable?.name ?? props.task.taskable_type));
</script>
