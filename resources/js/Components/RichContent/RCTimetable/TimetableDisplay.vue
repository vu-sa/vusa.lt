<template>
  <div
    v-if="rows.length || editable"
    class="overflow-hidden border border-border bg-secondary/30"
  >
    <div class="flex items-center justify-between border-b border-border bg-secondary/40 px-5 py-3">
      <div class="flex items-center gap-2">
        <IFluentClock20Regular class="size-4 text-brand" />
        <RCInlineText
          v-if="editable"
          as="h3"
          class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground"
          :model-value="element.options?.title ?? ''"
          :editable
          :placeholder="$t('Tvarkaraštis')"
          @click.stop
          @update:model-value="updateTitle"
        />
        <h3 v-else class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
          {{ heading }}
        </h3>
      </div>
      <Button
        v-if="editable && rows.length"
        variant="ghost"
        size="sm"
        class="h-7 text-xs text-muted-foreground hover:text-foreground"
        data-rc-interactive
        @click="addRow"
      >
        <IFluentAdd12Regular class="mr-1 size-3.5" />
        {{ $t('rich-content.add_timetable_row') }}
      </Button>
    </div>

    <!-- Empty state when editable and 0 rows -->
    <div
      v-if="editable && !rows.length"
      class="flex flex-col items-center justify-center p-8 text-center"
      data-rc-interactive
    >
      <IFluentClock20Regular class="mb-2 size-8 text-muted-foreground" />
      <p class="text-sm font-medium text-foreground">
        {{ $t('rich-content.no_timetable_rows') }}
      </p>
      <Button variant="outline" size="sm" class="mt-3" @click="addRow">
        <IFluentAdd12Regular class="mr-1 size-3.5" />
        {{ $t('rich-content.add_timetable_row') }}
      </Button>
    </div>

    <!-- Rows list -->
    <div v-else class="divide-y divide-border">
      <div
        v-for="(row, index) in rows"
        :key="index"
        class="group/row flex items-center gap-4 px-5 py-3 transition-colors hover:bg-secondary/20"
      >
        <!-- Time display / inputs -->
        <div v-if="editable" class="flex w-32 shrink-0 items-center gap-1 tabular-nums text-sm font-bold text-brand" data-rc-interactive>
          <input
            type="time"
            :value="row.startTime"
            class="w-14 rounded border border-transparent bg-transparent p-0.5 text-center font-bold text-brand hover:border-border focus:border-brand focus:bg-background focus:outline-none"
            @change="updateRowTime(index, 'startTime', ($event.target as HTMLInputElement).value)"
          >
          <span>–</span>
          <input
            type="time"
            :value="row.endTime"
            class="w-14 rounded border border-transparent bg-transparent p-0.5 text-center font-bold text-brand hover:border-border focus:border-brand focus:bg-background focus:outline-none"
            @change="updateRowTime(index, 'endTime', ($event.target as HTMLInputElement).value)"
          >
        </div>
        <span v-else class="w-28 shrink-0 text-base font-bold tabular-nums text-brand">{{ timeRangeLabel(row) }}</span>

        <!-- Title -->
        <div class="min-w-0 flex-1">
          <RCInlineText
            v-if="editable"
            as="span"
            class="block truncate text-sm font-medium text-foreground"
            :model-value="row.title"
            :editable
            :placeholder="$t('rich-content.enter_title')"
            @click.stop
            @update:model-value="updateRowTitle(index, $event)"
          />
          <span v-else class="block truncate text-sm font-medium text-foreground">{{ row.title }}</span>
        </div>

        <!-- Row actions when editable -->
        <div
          v-if="editable"
          class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover/row:opacity-100"
          data-rc-interactive
        >
          <button
            type="button"
            class="rounded p-1 text-muted-foreground hover:text-foreground disabled:opacity-30"
            :disabled="index === 0"
            :title="$t('rich-content.move_up')"
            @click.stop="moveRow(index, -1)"
          >
            <IFluentArrowUp12Regular class="size-3.5" />
          </button>
          <button
            type="button"
            class="rounded p-1 text-muted-foreground hover:text-foreground disabled:opacity-30"
            :disabled="index === rows.length - 1"
            :title="$t('rich-content.move_down')"
            @click.stop="moveRow(index, 1)"
          >
            <IFluentArrowDown12Regular class="size-3.5" />
          </button>
          <button
            type="button"
            class="rounded p-1 text-muted-foreground hover:text-destructive"
            :title="$t('rich-content.delete')"
            @click.stop="removeRow(index)"
          >
            <IFluentDelete12Regular class="size-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, defineAsyncComponent } from 'vue';

import type { Timetable } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import IFluentClock20Regular from '~icons/fluent/clock20-regular';
import IFluentAdd12Regular from '~icons/fluent/add-12-regular';
import IFluentArrowUp12Regular from '~icons/fluent/arrow-up-12-regular';
import IFluentArrowDown12Regular from '~icons/fluent/arrow-down-12-regular';
import IFluentDelete12Regular from '~icons/fluent/delete-12-regular';

// Lazy-loaded: only ever mounted while `editable` — a static import would bundle the
// full-screen editor's inline-text control into every public page that renders a
// timetable, which never reaches this branch at all.
const RCInlineText = defineAsyncComponent(() => import('../Editor/Fullscreen/RCInlineText.vue'));

const props = defineProps<{
  element: Timetable;
  editable?: boolean;
  blockKey?: string;
}>();

const emit = defineEmits<{
  (e: 'update:element', value: Timetable): void;
}>();

/** MySQL TIME arrives as `HH:MM:SS`; the timetable shows `HH:MM`. */
const trimSeconds = (value?: string | null): string => (value ? value.slice(0, 5) : '');

const rawContent = computed<Timetable['json_content']>(() => {
  const content = props.element.json_content as Timetable['json_content'] | undefined;
  return content ?? [];
});

const rows = computed<Timetable['json_content']>(() => {
  if (props.editable) {
    return rawContent.value;
  }
  return [...rawContent.value]
    .filter(row => Boolean(row?.startTime))
    .sort((a, b) => String(a.startTime).localeCompare(String(b.startTime)));
});

const heading = computed(() => (props.element.options?.title as string | undefined) ?? $t('Tvarkaraštis'));

function timeRangeLabel(row: Timetable['json_content'][number]): string {
  const start = trimSeconds(row.startTime);
  return row.endTime ? `${start}–${trimSeconds(row.endTime)}` : start;
}

function updateTitle(title: string): void {
  emit('update:element', {
    ...props.element,
    options: {
      ...props.element.options,
      title,
    },
  });
}

function addRow(): void {
  emit('update:element', {
    ...props.element,
    json_content: [
      ...rawContent.value,
      { startTime: '09:00', endTime: '10:00', title: '' },
    ],
  });
}

function updateRowTitle(index: number, title: string): void {
  const next = [...rawContent.value];
  if (next[index]) {
    next[index] = { ...next[index], title };
    emit('update:element', { ...props.element, json_content: next });
  }
}

function updateRowTime(index: number, field: 'startTime' | 'endTime', value: string): void {
  const next = [...rawContent.value];
  if (next[index]) {
    next[index] = { ...next[index], [field]: value };
    emit('update:element', { ...props.element, json_content: next });
  }
}

function removeRow(index: number): void {
  const next = [...rawContent.value];
  next.splice(index, 1);
  emit('update:element', { ...props.element, json_content: next });
}

function moveRow(fromIndex: number, delta: number): void {
  const toIndex = fromIndex + delta;
  if (toIndex < 0 || toIndex >= rawContent.value.length) return;
  const next = [...rawContent.value];
  const item = next.splice(fromIndex, 1)[0];
  if (item) {
    next.splice(toIndex, 0, item);
    emit('update:element', { ...props.element, json_content: next });
  }
}
</script>
