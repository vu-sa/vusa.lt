<template>
  <div ref="wrapper" />
</template>

<script setup lang="ts">
import { onMounted, useTemplateRef, watch } from 'vue';
import { binX, plot, rectY } from "@observablehq/plot";

const { allTenantMeetings, width, height } = defineProps<{
  allTenantMeetings: any;
  width?: number;
  height?: number;
}>();

const wrapper = useTemplateRef<HTMLDivElement>("wrapper");

const generatePlot = () => plot({
  x: { type: "time", label: "Laikas" },
  // don't show decimal
  y: { grid: true, label: "Susitikimų skaičius", round: true, nice: true, ticks: 3 },
  marks: [
    rectY(allTenantMeetings, binX({ y: "count" }, {
      x: "start_time", fill: '#aa2430ee', interval: 'month', tip: "x"
    })),
  ],
  marginTop: 30,
  marginBottom: 45,
  width: width,
  height: height,
});

watch(() => allTenantMeetings, () => {
  if (wrapper.value) {
    wrapper.value.innerHTML = ''
    wrapper.value.appendChild(generatePlot())
  }
});

onMounted(() => {
  if (wrapper.value) {
    wrapper.value?.appendChild(generatePlot());
  }
});
</script>

<style scoped>
/* NOTE: For some reason, the tooltip doesn't respond to styling */
::v-deep(g[aria-label="tip"]) {
  color: #000;
}
</style>
