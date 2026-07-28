<template>
  <div :class="surfaceClass">
    <div v-if="element.options?.title" data-slot="card-header" class="px-5 pt-5 pb-3">
      <h3 data-slot="card-title" class="text-lg font-bold leading-tight tracking-tight mb-1.5" :class="titleClass">
        {{ element.options.title }}
      </h3>
    </div>
    <div class="rc-prose tracking-normal px-5 pb-5" :class="{ 'pt-5': !element.options?.title }">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  element: models.ContentPart;
}>();

const color = computed(() => (props.element.options?.color as 'zinc' | 'red' | 'yellow' | undefined) ?? 'zinc');
const variant = computed(() => (props.element.options?.variant as 'outline' | 'soft' | undefined) ?? 'outline');

// Static class maps (no inline :style, no useDark) — dark: variants handle theming.
// `color` is an accent only now (a left rail on `outline`), never a tinted background —
// red/yellow washes read as alerts, which most cards aren't.
const SURFACE: Record<string, string> = {
  outline: 'bg-gradient-to-br from-zinc-50 to-zinc-100/50 ring-1 ring-zinc-200/60 hover:shadow-lg hover:ring-zinc-300 dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50 dark:hover:ring-zinc-600',
  soft: 'bg-gradient-to-br from-zinc-100 to-zinc-50 ring-0 shadow-sm hover:shadow-md dark:from-zinc-800 dark:to-zinc-900/60',
};

const ACCENT_RAIL: Record<string, string> = {
  red: `before:absolute before:inset-y-0 before:left-0 before:w-1 before:rounded-l-2xl before:bg-vusa-red/70 before:content-['']`,
  yellow: `before:absolute before:inset-y-0 before:left-0 before:w-1 before:rounded-l-2xl before:bg-vusa-yellow/80 before:content-['']`,
};

const TITLE_ACCENT: Record<string, string> = {
  zinc: 'text-zinc-900 dark:text-zinc-100',
  red: 'text-vusa-red dark:text-red-400',
  yellow: 'text-yellow-700 dark:text-vusa-yellow',
};

const surfaceClass = computed(() => {
  const surface = SURFACE[variant.value] ?? SURFACE.outline;
  // The accent rail only makes sense on `outline` — `soft` already tints the whole
  // surface, so a rail on top of that would be redundant.
  const rail = variant.value === 'outline' ? (ACCENT_RAIL[color.value] ?? '') : '';
  return `group relative flex flex-col overflow-hidden rounded-2xl transition-all duration-300 ${surface} ${rail}`;
});

const titleClass = computed(() => {
  if (!props.element.options?.isTitleColored) return TITLE_ACCENT.zinc;
  return TITLE_ACCENT[color.value] ?? TITLE_ACCENT.zinc;
});
</script>
