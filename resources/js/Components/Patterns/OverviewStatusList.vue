<template>
  <section v-if="rows.length > 0" class="flex flex-col gap-3" data-slot="overview-status-list" :aria-labelledby="headingId">
    <h2 :id="headingId" class="flex items-center gap-2 border-b border-border pb-3 text-sm font-bold uppercase tracking-[0.18em] text-foreground">
      <CircleCheck class="size-4 shrink-0 text-brand" aria-hidden="true" />
      {{ $t('shell.chrome.all_clear') }}
    </h2>
    <ul class="divide-y divide-border/60">
      <li v-for="entry in rows" :key="entry.id" class="flex items-start gap-3 py-3 text-sm">
        <component :is="entry.icon ?? CircleCheck" class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
        <span class="min-w-0 flex-1">
          <span class="font-bold text-foreground">{{ entry.title }}</span>
          <span v-if="entry.emptyText" class="text-muted-foreground"> — {{ entry.emptyText }}</span>
        </span>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { CircleCheck } from 'lucide-vue-next';
import { computed, inject, onBeforeUnmount, useId } from 'vue';

import { OVERVIEW_STATUS_KEY, type OverviewStatusEntry } from './overviewStatus';

const props = defineProps<{
  /** Omit inside an OverviewPage to place the page's list here instead of at its end. */
  entries?: (OverviewStatusEntry & { id: string })[];
}>();

const registry = props.entries ? null : inject(OVERVIEW_STATUS_KEY, null);

if (registry) {
  registry.placed.value++;
  onBeforeUnmount(() => registry.placed.value--);
}

const rows = computed(() => props.entries
  ?? [...(registry?.entries ?? [])].map(([id, entry]) => ({ id, ...entry })));

const headingId = useId();
</script>
