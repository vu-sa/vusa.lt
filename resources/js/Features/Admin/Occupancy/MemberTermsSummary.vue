<template>
  <Popover :open @update:open="onOpenChange">
    <PopoverTrigger as-child>
      <button
        type="button"
        class="u-touch inline-flex flex-wrap items-center gap-x-1.5 text-left text-sm tabular-nums underline decoration-dotted decoration-muted-foreground underline-offset-4 hover:text-brand"
        data-testid="member-terms-summary"
        @pointerenter="onPointerEnter"
        @pointerleave="onPointerLeave"
      >
        <slot>
          <span v-if="currentCount > 0" class="font-medium text-foreground">{{ $t(':count dabar', { count: String(currentCount) }) }}</span>
          <!-- "1 dabar · 1 iš viso" says the same thing twice. -->
          <template v-if="currentCount !== totalCount">
            <span v-if="currentCount > 0" class="text-muted-foreground" aria-hidden="true">·</span>
            <span class="text-muted-foreground">{{ $t(':count iš viso', { count: String(totalCount) }) }}</span>
          </template>
        </slot>
      </button>
    </PopoverTrigger>
    <PopoverContent
      align="start"
      class="w-72 space-y-3 p-3 text-sm"
      data-testid="member-terms-list"
      @pointerenter="onPointerEnter"
      @pointerleave="onPointerLeave"
      @open-auto-focus="skipFocusOnHover"
      @close-auto-focus="skipFocusOnHover"
    >
      <section v-for="group in groups" :key="group.key" class="space-y-1">
        <h3 class="text-xs font-medium text-muted-foreground">
          {{ group.title }}
        </h3>
        <ul class="space-y-0.5">
          <li v-for="item in group.items" :key="item.name" class="flex items-baseline justify-between gap-3">
            <span class="min-w-0 truncate">{{ item.name }}</span>
            <span v-if="item.period" class="shrink-0 text-xs tabular-nums text-muted-foreground">{{ item.period }}</span>
          </li>
        </ul>
      </section>
      <p v-if="groups.length === 0" class="text-muted-foreground">
        {{ loading ? $t('Kraunami duomenys') : emptyText ?? $t('Institucijų nėra') }}
      </p>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import { termStatus, termYearSpan, type TermStatus } from './occupancy';

import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

interface MemberTerm {
  /** Names the line itself, for a list of people rather than institutions. */
  label?: string | null;
  institution?: { name?: string | null; short_name?: string | null } | null;
  pivot?: { start_date?: string | null; end_date?: string | null } | null;
}

/** The trigger defaults to "2 dabar · 5 iš viso"; the slot replaces it (e.g. with the active years). */
const props = withDefaults(defineProps<{
  duties: MemberTerm[];
  currentCount?: number;
  totalCount?: number;
  loading?: boolean;
  emptyText?: string;
}>(), {
  currentCount: 0,
  totalCount: 0,
  emptyText: undefined,
});

const emit = defineEmits<{
  /** Lets a caller load the terms only when someone looks at them. */
  open: [];
}>();

const open = ref(false);
const openedByHover = ref(false);
let closeTimer: ReturnType<typeof setTimeout> | undefined;
let pointerInside = false;

// Clicking a list that hover already opened pins it instead of toggling it shut.
const onOpenChange = (value: boolean) => {
  if (!value && openedByHover.value && pointerInside) {
    openedByHover.value = false;
    return;
  }

  open.value = value;
};

// A peek on hover must not move focus in and back out, which leaves a focus ring on the trigger.
const skipFocusOnHover = (event: Event) => {
  if (openedByHover.value) {
    event.preventDefault();
  }
};

// Opens on hover for a mouse, like a tooltip; touch and keyboard open it with a tap or Enter.
const onPointerEnter = (event: PointerEvent) => {
  if (event.pointerType !== 'mouse') {
    return;
  }

  clearTimeout(closeTimer);
  pointerInside = true;

  if (!open.value) {
    openedByHover.value = true;
    open.value = true;
  }
};

const onPointerLeave = (event: PointerEvent) => {
  if (event.pointerType !== 'mouse') {
    return;
  }

  pointerInside = false;
  closeTimer = setTimeout(() => {
    if (openedByHover.value) {
      open.value = false;
    }
  }, 150);
};

// A click or tap opens it for real: focus then moves in so the list is reachable by keyboard.
watch(open, (isOpen) => {
  if (isOpen) {
    emit('open');
  }
  else {
    openedByHover.value = false;
  }
});

onBeforeUnmount(() => clearTimeout(closeTimer));

/**
 * One line per institution with the period served there: "2023–dabar" where they still serve
 * (earlier terms there included), "2019–2021" for the past, the starting year for what is ahead.
 */
const groups = computed(() => {
  const byStatus: Record<TermStatus, Map<string, MemberTerm['pivot'][]>> = { current: new Map(), upcoming: new Map(), ended: new Map() };
  const all = new Map<string, MemberTerm['pivot'][]>();

  for (const term of props.duties) {
    const name = term.label || term.institution?.short_name || term.institution?.name;

    if (!name) {
      continue;
    }

    const bucket = byStatus[termStatus(term.pivot ?? {})];
    bucket.set(name, [...(bucket.get(name) ?? []), term.pivot]);
    all.set(name, [...(all.get(name) ?? []), term.pivot]);
  }

  // Somewhere the person still serves is not repeated under "Anksčiau".
  for (const name of byStatus.current.keys()) {
    byStatus.ended.delete(name);
  }

  const period = (terms: MemberTerm['pivot'][]) => termYearSpan(terms.map(term => term ?? {}), $t('dabar'));
  const toItems = (entries: Map<string, MemberTerm['pivot'][]>, span: (name: string, terms: MemberTerm['pivot'][]) => string | null) => (
    [...entries].map(([name, terms]) => ({ name, period: span(name, terms) }))
  );

  return [
    { key: 'current', title: $t('Dabar'), items: toItems(byStatus.current, name => period(all.get(name) ?? [])) },
    {
      key: 'upcoming',
      title: $t('Netrukus'),
      items: toItems(byStatus.upcoming, (_, terms) => terms.map(term => String(term?.start_date ?? '').slice(0, 4)).filter(Boolean).sort()[0] ?? null),
    },
    {
      key: 'ended',
      title: $t('Anksčiau'),
      // Most recent first: the period string ends with the year they left.
      items: toItems(byStatus.ended, (_, terms) => period(terms)).sort((a, b) => String(b.period).slice(-4).localeCompare(String(a.period).slice(-4))),
    },
  ].filter(group => group.items.length > 0);
});
</script>
