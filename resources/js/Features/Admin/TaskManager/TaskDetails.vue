<template>
  <div class="space-y-4" data-slot="task-details">
    <div class="flex flex-wrap items-center gap-2 text-sm">
      <Badge v-if="task.is_overdue && !task.completed_at" variant="rose" class="gap-1">
        <AlertCircleIcon class="h-3 w-3" />
        {{ $t('overdue') }}
      </Badge>
      <Badge v-if="task.can_be_manually_completed === false" variant="secondary" class="gap-1">
        <RotateCwIcon class="h-3 w-3" />
        {{ $t('tasks.auto_completing') }}
      </Badge>
      <span v-if="task.due_date" class="text-muted-foreground">
        {{ $t('tasks.due') }}: {{ formatDate(task.due_date) }}
      </span>
    </div>

    <div v-if="task.description" class="border-l-2 border-border pl-3">
      <h4 class="mb-1 flex items-center gap-2 text-sm font-medium text-foreground">
        <InfoIcon class="h-4 w-4" />
        {{ $t('tasks.instructions') }}
      </h4>
      <p class="whitespace-pre-line text-sm text-muted-foreground">
        {{ task.description }}
      </p>
    </div>

    <Button v-if="showPeriodicityActions" variant="brand" class="w-full sm:w-auto" @click="emit('report')">
      <Megaphone aria-hidden="true" />
      {{ $t('tasks.periodicity_gap.report_no_meeting') }}
    </Button>

    <!-- A meeting task can carry the whole institution, so assignees are avatars with the names
         behind a hover card rather than a pill per person. -->
    <div v-if="task.users?.length" class="border-t border-border pt-4">
      <div class="mb-2 flex items-center gap-2">
        <h4 class="text-sm font-medium text-foreground">
          {{ $t('tasks.assigned_to') }}
        </h4>
        <Badge variant="secondary" class="tabular-nums">
          {{ task.users.length }}
        </Badge>
      </div>
      <UsersAvatarGroup :users="task.users" :size="28" :max="8" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  AlertCircle as AlertCircleIcon,
  Info as InfoIcon,
  Megaphone,
  RotateCw as RotateCwIcon,
} from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { isPeriodicityGapTask, type TaskDisplayData } from '@/Composables/useTaskPresentation';

const props = defineProps<{
  task: TaskDisplayData;
  /** The collection preview offers these in its own action row. */
  hideActions?: boolean;
}>();

const emit = defineEmits<{
  /** Opens the action window: record a meeting, or say there was none. */
  report: [];
}>();

const showPeriodicityActions = computed(() => !props.hideActions && !props.task.completed_at && isPeriodicityGapTask(props.task));

const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString('lt-LT', {
  year: 'numeric',
  month: 'long',
  day: 'numeric',
});
</script>
