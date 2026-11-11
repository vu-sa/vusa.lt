<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { CheckCheck } from '@lucide/vue';

import type { WikiProgressFlash } from './wikiProgressToast';

const props = defineProps<{ progress: WikiProgressFlash }>();

const DURATION = 700;
const shownDone = ref(props.progress.previousDone);
const percentOf = (done: number) => props.progress.total === 0 ? 0 : Math.min(100, done / props.progress.total * 100);
const width = ref(percentOf(props.progress.previousDone));
const complete = computed(() => props.progress.total > 0 && props.progress.done >= props.progress.total);
let frame = 0;

// The bar grows from where you were to where you are, and the count follows it.
onMounted(() => {
  const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  frame = requestAnimationFrame(() => {
    width.value = percentOf(props.progress.done);
  });
  if (reduced || props.progress.done === props.progress.previousDone) {
    shownDone.value = props.progress.done;
    return;
  }
  const start = performance.now();
  const tick = (now: number) => {
    const t = Math.min(1, (now - start) / DURATION);
    shownDone.value = Math.round(props.progress.previousDone + (props.progress.done - props.progress.previousDone) * t);
    if (t < 1) frame = requestAnimationFrame(tick);
  };
  frame = requestAnimationFrame(tick);
});
onBeforeUnmount(() => cancelAnimationFrame(frame));
</script>

<template>
  <div class="w-[min(356px,calc(100vw-2rem))] border border-border bg-popover p-4 text-popover-foreground" role="status" data-slot="wiki-progress-toast">
    <p class="flex items-start gap-2 text-sm leading-snug">
      <CheckCheck class="mt-0.5 size-4 shrink-0 text-status-success" aria-hidden="true" />
      <span class="min-w-0"><span class="font-semibold">{{ $t('learning.wiki.toast_learned') }}</span> {{ progress.title }}</span>
    </p>
    <div class="mt-3 flex items-baseline justify-between gap-3 text-xs text-muted-foreground">
      <span>{{ $t(complete ? 'learning.wiki.toast_complete' : 'learning.wiki.progress_all') }}</span>
      <span class="font-semibold tabular-nums text-foreground" data-slot="wiki-progress-toast-count">{{ shownDone }}/{{ progress.total }}</span>
    </div>
    <div class="mt-1.5 h-2 border border-foreground" role="progressbar" :aria-valuenow="progress.done" :aria-valuemin="0" :aria-valuemax="progress.total" :aria-label="$t('learning.wiki.progress_all')">
      <div class="h-full bg-brand-fill transition-[width] duration-700 ease-out" :style="{ width: `${width}%` }" data-slot="wiki-progress-toast-bar" />
    </div>
  </div>
</template>
