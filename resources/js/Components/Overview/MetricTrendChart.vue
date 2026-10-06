<template>
  <div ref="wrapper" class="w-full overflow-x-auto" data-slot="metric-trend-chart" />
</template>

<script setup lang="ts">
import { dot, line, plot, ruleY } from '@observablehq/plot';
import { nextTick, onMounted, ref, watch } from 'vue';

import type { MetricPoint } from './metricTrend';

const props = defineProps<{
  points: MetricPoint[];
  /** Drawn as a rule so the line reads against what it is supposed to reach. */
  target?: number | null;
}>();

const wrapper = ref<HTMLElement | null>(null);

// The brand token, not a hex: dark mode swaps it without this file knowing.
function draw() {
  if (!wrapper.value) {
    return;
  }

  const measured = props.points
    .filter(point => point.value !== null)
    .map(point => ({ date: new Date(`${point.month}-01T00:00:00`), value: point.value as number }));

  wrapper.value.innerHTML = '';
  wrapper.value.appendChild(plot({
    x: { type: 'time', label: null },
    y: { domain: [0, 100], grid: true, label: null, ticks: 4 },
    marks: [
      ruleY([0]),
      ...(props.target == null ? [] : [ruleY([props.target], { stroke: 'var(--muted-foreground)', strokeDasharray: '4 3' })]),
      line(measured, { x: 'date', y: 'value', stroke: 'var(--brand-fill)', strokeWidth: 2 }),
      dot(measured, { x: 'date', y: 'value', fill: 'var(--brand-fill)', r: 3 }),
    ],
    marginTop: 20,
    marginBottom: 30,
    marginLeft: 35,
    width: 720,
    height: 200,
  }));
}

onMounted(draw);
watch(() => [props.points, props.target], async () => {
  await nextTick();
  draw();
}, { deep: true });
</script>
