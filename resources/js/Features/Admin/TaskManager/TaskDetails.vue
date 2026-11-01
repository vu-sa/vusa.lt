<template>
  <div class="space-y-4" data-slot="task-details">
    <p v-if="task.cancelled_at" class="text-sm text-muted-foreground" data-slot="task-cancelled">
      {{ $t('tasks.cancelled_hint') }}
    </p>

    <p v-if="task.description" class="whitespace-pre-line text-sm text-foreground">
      {{ task.description }}
    </p>

    <ContextFacts class="gap-x-6 gap-y-2 pt-3 text-sm">
      <ContextFact v-if="task.due_date" :label="$t('tasks.due')" :value="formatDate(task.due_date)" />
      <ContextFact v-if="task.progress" :label="$t('tasks.detail.progress')" :value="`${task.progress.current} / ${task.progress.total}`" />
      <ContextFact v-if="closedOn" :label="$t('tasks.detail.completed_on')" :value="closedOn" />
      <ContextFact v-if="task.can_be_manually_completed === false && !task.completed_at" :label="$t('tasks.detail.completing')" :value="$t('tasks.detail.completes_itself')" />
      <ContextFact v-if="task.users?.length" :label="$t('tasks.assigned_to')">
        <span class="flex min-w-0 items-center gap-2">
          <UsersAvatarGroup :users="task.users" :size="28" :max="5" :expandable="task.users.length > 1" />
          <span v-if="task.users.length === 1" class="min-w-0 truncate">{{ task.users[0]?.name }}</span>
        </span>
      </ContextFact>
    </ContextFacts>
  </div>
</template>

<script setup lang="ts">
import { format, parseISO } from 'date-fns';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { ContextFact, ContextFacts } from '@/Components/Patterns';
import { useDateLocale } from '@/Composables/useDateLocale';
import type { TaskDisplayData } from '@/Composables/useTaskPresentation';

const props = defineProps<{
  task: TaskDisplayData;
}>();

const dateLocale = useDateLocale();

const formatDate = (date: string) => format(parseISO(date), 'PPP', { locale: dateLocale.value });

// A cancelled task carries `completed_at` too, but nobody completed it.
const closedOn = computed(() => (props.task.completed_at && !props.task.cancelled_at ? formatDate(props.task.completed_at) : null));
</script>
