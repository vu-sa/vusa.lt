<template>
  <component
    :is="interactive ? 'button' : 'div'"
    :type="interactive ? 'button' : undefined"
    :aria-pressed="interactive ? active : undefined"
    :class="[
      'group flex flex-col items-start rounded-lg border px-3 py-2 text-left transition-colors',
      // Without a border these read as floating text against the page background.
      'border-zinc-200 dark:border-zinc-800',
      interactive ? 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' : '',
      interactive ? 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300' : '',
      // A selected tile is a live filter, so it has to be unmistakable.
      active ? 'border-zinc-900 bg-zinc-100 dark:border-zinc-100 dark:bg-zinc-800' : '',
      muted ? 'opacity-60' : '',
    ]"
    @click="interactive && emit('click')"
  >
    <span
      :class="[
        'flex items-center gap-2 text-sm',
        active ? 'font-medium text-foreground' : 'text-muted-foreground',
      ]"
    >
      <slot name="indicator">
        <span v-if="dot" :class="['size-2 shrink-0 rounded-full', dot]" />
        <component :is="icon" v-else-if="icon" :class="['size-4 shrink-0', iconClass]" />
      </slot>
      {{ label }}
      <slot name="badge" />
    </span>

    <!-- The count stays in the default foreground whatever it reports: tinting one number in a
         row of four stops them reading as comparable quantities. Signal urgency on the icon. -->
    <span class="mt-1 text-4xl font-bold tabular-nums">
      <slot name="value">{{ value }}</slot>
    </span>

    <span v-if="caption" class="mt-0.5 text-xs text-muted-foreground">
      {{ caption }}
    </span>
  </component>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

/**
 * The standard admin KPI tile: a label with an indicator, a large count, and a caption.
 *
 * Use it for any "N of these" summary above a listing, so the task summary, the reservations
 * dashboard and anything added later read as one thing. Wrap a set of them in a grid — two
 * columns on a phone, four on a wide screen is the house layout.
 */
withDefaults(defineProps<{
  label: string;
  /** A string is allowed so callers can pre-format (e.g. "1.2k"). Override with the `value` slot. */
  value?: number | string;
  caption?: string;
  /** Colour class for a status dot, e.g. `bg-emerald-500`. Mutually exclusive with `icon`. */
  dot?: string;
  icon?: Component;
  iconClass?: string;
  /** Renders as a button and emits `click` — for a tile that filters the list below it. */
  interactive?: boolean;
  /** The filter this tile stands for is the one currently applied. */
  active?: boolean;
  /** Dimmed, for a tile whose count is zero. */
  muted?: boolean;
}>(), {
  value: undefined,
  caption: undefined,
  dot: undefined,
  icon: undefined,
  iconClass: undefined,
  interactive: false,
  active: false,
  muted: false,
});

const emit = defineEmits<{
  (e: 'click'): void;
}>();
</script>
