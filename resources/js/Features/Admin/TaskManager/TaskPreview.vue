<template>
  <PreviewPane entity-type="task" :eyebrow="getTaskActionLabel(task.action_type) || $t('tasks.detail.eyebrow')" :title="task.name">
    <template #mark>
      <span
        :class="['flex size-5 shrink-0 items-center justify-center', parts.surface, parts.border, parts.text]"
        :data-status-role="status.role"
        :title="$t(status.label)"
        data-slot="task-status-mark"
      >
        <component :is="getTaskActionIcon(task.action_type)" class="size-3.5" aria-hidden="true" />
        <span class="sr-only">{{ $t(status.label) }}</span>
      </span>
    </template>

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

    <template v-if="actions?.length" #actions>
      <CollectionRowActions :actions emphasis full-labels @select="key => emit('select', key as TaskActionKey)" />
    </template>

    <TaskDetails :task />
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import type { TaskAction, TaskActionKey } from './taskActions';
import TaskDetails from './TaskDetails.vue';

import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import { PreviewPane } from '@/Components/Patterns';
import { statusRoleParts, taskStatuses } from '@/Constants/statuses';
import { getTaskActionIcon, getTaskActionLabel, getTaskStatus, getTaskableUrl, isOrphanedTask, type TaskDisplayData } from '@/Composables/useTaskPresentation';

const props = defineProps<{
  task: TaskDisplayData;
  actions?: TaskAction[];
}>();

const emit = defineEmits<{
  select: [key: TaskActionKey];
}>();

const status = computed(() => taskStatuses[getTaskStatus(props.task) ?? 'open']);
const parts = computed(() => statusRoleParts[status.value.role]);
const taskableUrl = computed(() => getTaskableUrl(props.task));
const subject = computed(() => (isOrphanedTask(props.task) ? $t('tasks.orphaned') : props.task.taskable?.name ?? props.task.taskable_type));
</script>
