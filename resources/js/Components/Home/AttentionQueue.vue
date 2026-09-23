<template>
  <section data-slot="attention-queue" data-tour="tasks-card" :aria-labelledby="headingId">
    <header class="flex items-center justify-between gap-4 border-b border-border pb-3">
      <h2 :id="headingId" class="text-base font-semibold text-foreground">
        {{ $t('Mano užduotys') }}
      </h2>
      <Link
        :href="moreHref"
        class="shrink-0 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
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
          <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center border border-border text-muted-foreground" aria-hidden="true">
            <component :is="task.icon" class="size-4" />
          </span>

          <span class="min-w-0 flex-1">
            <span class="block font-semibold text-foreground">{{ task.name }}</span>
            <span v-if="task.context" class="mt-0.5 block text-sm text-muted-foreground">{{ task.context }}</span>
          </span>

          <span class="flex shrink-0 flex-col items-end gap-1.5 text-sm">
            <StatusBadge v-if="task.badge" :status="task.badge" />
            <span v-if="task.due" class="text-muted-foreground">{{ task.due }}</span>
          </span>
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
import { ClipboardCheck } from 'lucide-vue-next';
import { computed, useId } from 'vue';

import type { HomeTask } from './types';

import { StatusBadge } from '@/Components/Patterns';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import { taskStatuses } from '@/Constants/statuses';
import { getMeetingAgendaUrl, getTaskableUrl } from '@/Composables/useTaskPresentation';
import { formatNearDate } from '@/Utils/dateTime';

const props = defineProps<{
  tasks: HomeTask[];
  stats: { total: number; overdue: number; dueSoon: number };
  moreHref: string;
}>();

const headingId = useId();

const DUE_SOON_DAYS = 7;

const rows = computed(() => props.tasks.map((task) => {
  const dueAt = task.due_date ? new Date(task.due_date) : null;
  const isDueSoon = dueAt !== null && !task.is_overdue
    && dueAt.getTime() - Date.now() <= DUE_SOON_DAYS * 24 * 60 * 60 * 1000;

  return {
    id: task.id,
    name: task.name,
    context: task.taskable?.name ?? null,
    // A meeting task lands on the agenda tab, ready to fill: one tap to the exact screen (R-a).
    href: getMeetingAgendaUrl(task) ?? getTaskableUrl(task),
    icon: getEntityTypeDefinition(task.taskable_type)?.icon ?? ClipboardCheck,
    // An on-time task is the healthy state and carries no badge.
    badge: task.is_overdue ? taskStatuses.overdue : isDueSoon ? taskStatuses.due_soon : null,
    due: dueAt ? formatNearDate(dueAt) : null,
  };
}));
</script>
