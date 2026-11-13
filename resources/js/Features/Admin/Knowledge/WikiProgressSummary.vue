<template>
  <dl :class="['w-full', !block && 'sm:w-40']" data-slot="wiki-progress" :aria-label="$t('learning.path.summary')">
    <div class="flex min-w-0 flex-col" data-slot="wiki-progress-all">
      <dt class="sr-only">
        {{ $t('learning.wiki.progress_all') }}
      </dt>
      <dd class="u-display order-1 text-4xl leading-none tabular-nums" data-slot="wiki-progress-count">
        {{ shownDone }}<span class="text-muted-foreground">/{{ total }}</span>
      </dd>
      <dd class="order-3 mt-2 h-2.5 border border-foreground" role="progressbar" :aria-label="$t('learning.wiki.progress_all')" :aria-valuenow="percent" :aria-valuemin="0" :aria-valuemax="100" :aria-valuetext="`${done}/${total}`">
        <div :class="['h-full bg-foreground', from === undefined ? 'transition-[width]' : 'transition-[width] duration-700 ease-out']" :style="{ width: `${percentOf(barDone)}%` }" data-slot="wiki-progress-bar" />
      </dd>
    </div>
  </dl>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
  done: number;
  total: number;
  /** Animate the count and bar up from here on mount, as the learned toast does. */
  from?: number;
  /** Fill the container instead of the home page's narrow column. */
  block?: boolean;
}>();

const DURATION = 700;
const shownDone = ref(props.from ?? props.done);
const barDone = ref(props.from ?? props.done);
const percentOf = (done: number) => props.total === 0 ? 0 : Math.min(100, Math.round(done / props.total * 100));
const percent = computed(() => percentOf(props.done));
let frame = 0;

watch(() => props.done, (done) => {
  shownDone.value = done;
  barDone.value = done;
});

onMounted(() => {
  if (props.from === undefined || props.from === props.done) return;
  const start = props.from;
  frame = requestAnimationFrame(() => {
    barDone.value = props.done;
  });
  if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
    shownDone.value = props.done;
    return;
  }
  const startedAt = performance.now();
  const tick = (now: number) => {
    const t = Math.min(1, (now - startedAt) / DURATION);
    shownDone.value = Math.round(start + (props.done - start) * t);
    if (t < 1) frame = requestAnimationFrame(tick);
  };
  frame = requestAnimationFrame(tick);
});
onBeforeUnmount(() => cancelAnimationFrame(frame));
</script>
