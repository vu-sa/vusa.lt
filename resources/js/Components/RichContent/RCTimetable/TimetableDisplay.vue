<template>
  <div
    v-if="rows.length"
    class="overflow-hidden border border-border bg-secondary/40"
  >
    <div class="flex items-center gap-2 border-b border-border px-5 py-3">
      <IFluentClock20Regular class="size-4 text-brand" />
      <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
        {{ heading }}
      </h3>
    </div>
    <div class="divide-y divide-border">
      <div
        v-for="(row, index) in rows"
        :key="index"
        class="flex items-center gap-4 px-5 py-3"
      >
        <span class="w-28 shrink-0 text-base font-bold tabular-nums text-brand">{{ timeRangeLabel(row) }}</span>
        <span class="min-w-0 flex-1 truncate text-sm font-medium text-foreground">{{ row.title }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import type { Timetable } from '@/Types/contentParts';
import IFluentClock20Regular from '~icons/fluent/clock20-regular';

const props = defineProps<{
  element: models.ContentPart;
}>();

/** MySQL TIME arrives as `HH:MM:SS`; the timetable shows `HH:MM`. */
const trimSeconds = (value?: string | null): string => (value ? value.slice(0, 5) : '');

const rows = computed(() => {
  const content = props.element.json_content as Timetable['json_content'] | undefined;
  return (content ?? [])
    .filter(row => Boolean(row?.startTime))
    .sort((a, b) => String(a.startTime).localeCompare(String(b.startTime)));
});

const heading = computed(() => (props.element.options?.title as string | undefined) ?? $t('Tvarkaraštis'));

function timeRangeLabel(row: Timetable['json_content'][number]): string {
  const start = trimSeconds(row.startTime);
  return row.endTime ? `${start}–${trimSeconds(row.endTime)}` : start;
}
</script>
