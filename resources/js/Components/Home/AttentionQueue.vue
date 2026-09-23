<template>
  <section data-slot="attention-queue" data-tour="tasks-card" :aria-labelledby="headingId">
    <header class="flex items-center justify-between gap-4 border-b border-border pb-3">
      <h2 :id="headingId" class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em] text-foreground">
        <ClipboardList class="size-4 shrink-0 text-brand" aria-hidden="true" />
        {{ $t('Mano užduotys') }}
      </h2>
      <Link
        :href="moreHref"
        class="shrink-0 text-xs font-bold uppercase tracking-wide text-brand hover:text-foreground"
      >
        {{ $t('Visos užduotys') }}
      </Link>
    </header>

    <ul v-if="tasks.length > 0" class="divide-y divide-border">
      <li v-for="task in rows" :key="task.id">
        <component
          :is="task.href ? Link : 'div'"
          :href="task.href ?? undefined"
          :prefetch="task.href ? true : undefined"
          class="flex items-start gap-3 py-4 hover:bg-secondary/40 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring pointer-coarse:min-h-11"
        >
          <span class="mt-1.5 size-2.5 shrink-0 bg-border" aria-hidden="true" />

          <span class="min-w-0 flex-1">
            <span class="block text-pretty font-bold text-foreground">{{ task.name }}</span>
            <span v-if="task.context" class="mt-0.5 block text-xs text-muted-foreground">{{ task.context }}</span>
          </span>

          <time
            v-if="task.due"
            :datetime="task.dueAt"
            :title="formatDateTime(task.dueAt)"
            :aria-label="task.isOverdue ? $t('home.overdue_due_date', { date: task.due }) : undefined"
            :class="['shrink-0 text-right text-xs font-bold', task.isOverdue ? 'text-brand' : 'text-muted-foreground']"
          >{{ task.due }}</time>
        </component>
      </li>
    </ul>
    <p v-else class="py-4 text-sm text-muted-foreground">
      {{ stats.total > 0 ? $t('Artimiausiu metu užduočių nėra') : $t('Šiuo metu nieko nelaukia') }}
    </p>
  </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ClipboardList } from 'lucide-vue-next';
import { computed, useId } from 'vue';

import type { HomeTask } from './types';

import { getMeetingAgendaUrl, getTaskableUrl } from '@/Composables/useTaskPresentation';
import { useDateFormatter } from '@/Composables/useDateFormatter';

const props = defineProps<{
  tasks: HomeTask[];
  stats: { total: number; overdue: number; dueSoon: number };
  moreHref: string;
}>();

const headingId = useId();

const { formatNearDate, formatDateTime } = useDateFormatter();

const rows = computed(() => props.tasks.map((task) => {
  return {
    id: task.id,
    name: task.name,
    context: task.taskable?.name ?? null,
    // A meeting task lands on the agenda tab, ready to fill: one tap to the exact screen (R-a).
    href: getMeetingAgendaUrl(task) ?? getTaskableUrl(task),
    isOverdue: task.is_overdue,
    dueAt: task.due_date,
    due: task.due_date ? formatNearDate(task.due_date, { thresholdDays: 9999 }) : null,
  };
}));
</script>
