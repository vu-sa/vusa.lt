<template>
  <div class="mb-6 grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
    <StatCard
      v-for="card in cards"
      :key="card.label"
      :label="card.label"
      :value="card.value"
      :caption="card.caption"
      :icon="card.icon"
      :icon-class="card.iconClass"
      :muted="card.value === 0"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  AlertCircle as AlertCircleIcon,
  CheckCircle as CheckCircleIcon,
  ClipboardList as ClipboardListIcon,
  RotateCw as RotateCwIcon,
} from 'lucide-vue-next';

import { StatCard } from '@/Components/Patterns';
import type { TaskStats } from '@/Composables/useTaskPresentation';
import { capitalize } from '@/Utils/String';

const props = defineProps<{
  taskStats: TaskStats;
}>();

// Several of these labels come from shared lowercase keys ("vėluoja", "atlikta"), so they are
// capitalised here rather than in the catalogue, which other callers use mid-sentence.
const cards = computed(() => [
  {
    label: capitalize($t('tasks.stats.pending')),
    value: props.taskStats.total,
    caption: $t('tasks.stats.pending_caption'),
    icon: ClipboardListIcon,
    iconClass: 'text-muted-foreground',
  },
  {
    label: capitalize($t('overdue')),
    value: props.taskStats.overdue,
    caption: $t('tasks.stats.overdue_caption'),
    icon: AlertCircleIcon,
    // The icon carries the alarm; the number stays in the default foreground so the four
    // counts remain comparable at a glance.
    iconClass: props.taskStats.overdue > 0 ? 'text-status-danger' : 'text-muted-foreground',
  },
  {
    label: capitalize($t('tasks.stats.auto_completing')),
    value: props.taskStats.autoCompleting,
    caption: $t('tasks.stats.auto_completing_caption'),
    icon: RotateCwIcon,
    iconClass: 'text-status-info',
  },
  {
    label: capitalize($t('completed')),
    value: props.taskStats.completed,
    caption: $t('tasks.stats.completed_caption'),
    icon: CheckCircleIcon,
    iconClass: 'text-status-success',
  },
]);
</script>
