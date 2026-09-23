<template>
  <OverviewSection
    data-slot="attention-queue"
    data-tour="tasks-card"
    :title="$t('home.tasks_title')"
    :icon="ClipboardList"
    variant="home"
    :href="moreHref"
    :href-label="remainingCount > 0 ? $t('ir dar :count', { count: String(remainingCount) }) : $t('Visos užduotys')"
    :empty="tasks.length === 0"
    :empty-text="stats.total > 0 ? $t('Artimiausiu metu užduočių nėra') : $t('Šiuo metu nieko nelaukia')"
  >
    <ul class="divide-y divide-border">
      <li v-for="task in rows" :key="task.id">
        <component
          :is="task.href ? Link : 'div'"
          :href="task.href ?? undefined"
          :prefetch="task.href ? true : undefined"
          class="flex items-start gap-3 py-4 hover:bg-secondary/40 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring pointer-coarse:min-h-11"
        >
          <span class="min-w-0 flex-1">
            <span class="block text-pretty font-bold text-foreground">{{ task.name }}</span>
            <span v-if="task.context" class="mt-0.5 block text-xs text-muted-foreground">{{ task.context }}</span>
          </span>

          <time
            v-if="task.due"
            :datetime="task.dueAt"
            :title="formatDateTime(task.dueAt)"
            :aria-label="task.isOverdue ? $t('home.overdue_due_date', { date: formatDateTime(task.dueAt) }) : undefined"
            :class="['shrink-0 text-right text-xs font-bold', task.isOverdue ? 'text-brand' : 'text-muted-foreground']"
          >{{ task.due }}</time>
        </component>
      </li>
    </ul>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ClipboardList } from 'lucide-vue-next';
import { computed } from 'vue';

import type { HomeTask } from './types';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { getMeetingAgendaUrl, getTaskableUrl } from '@/Composables/useTaskPresentation';
import { useDateFormatter } from '@/Composables/useDateFormatter';

const props = defineProps<{
  tasks: HomeTask[];
  stats: { total: number; overdue: number; dueSoon: number };
  remainingCount?: number;
  moreHref: string;
}>();

const { formatNearDate, formatDateTime } = useDateFormatter();

const DAY_MS = 86_400_000;

// A past due date reads "vėluoja 2 d.", not "prieš 2 d.", so it never looks like a creation time.
const dueLabel = (task: HomeTask): string | null => {
  if (!task.due_date) {
    return null;
  }

  if (!task.is_overdue) {
    return formatNearDate(task.due_date, { thresholdDays: 9999 });
  }

  const days = Math.floor((Date.now() - new Date(task.due_date).getTime()) / DAY_MS);

  return days > 0 ? $t('home.overdue_by', { days: String(days) }) : $t('home.overdue');
};

const rows = computed(() => props.tasks.map((task) => {
  return {
    id: task.id,
    name: task.name,
    context: task.taskable?.name ?? null,
    // A meeting task lands on the agenda tab, ready to fill: one tap to the exact screen (R-a).
    href: getMeetingAgendaUrl(task) ?? getTaskableUrl(task),
    isOverdue: task.is_overdue,
    dueAt: task.due_date,
    due: dueLabel(task),
  };
}));
</script>
