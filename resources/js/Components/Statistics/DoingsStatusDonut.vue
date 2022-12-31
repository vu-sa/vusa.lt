<template>
  <div ref="donut" class="w-fit"></div>
</template>

<script setup lang="tsx">
import { arc, create, group, pie, rollup, scaleOrdinal, select } from "d3";
import { onBeforeUnmount, onMounted, ref } from "vue";

const props = defineProps<{
  doings?: App.Models.Doing[];
  width: number;
  height: number;
}>();

const donut = ref(null);
const d3 = { select, create, scaleOrdinal, pie, arc, group, rollup };

const radius = Math.min(props.width, props.height) / 2;

const svg = d3
  .create("svg")
  .attr("width", props.width)
  .attr("height", props.height);

const groupedData = d3.rollup(
  props.doings,
  (v) => v.length,
  (d) => d.status
);

const reconstructedData = Array.from(groupedData, ([key, value]) => ({
  key,
  value,
}));

console.log(groupedData, reconstructedData);

const keyColors = {
  Sukurtas: "#2080f0",
  Pabaigtas: "#18a058",
  "Sukurtas per vėlai": "#bd2835",
  "Sukurtas po įvykio": "#bd2835",
};

const colorScale = d3
  .scaleOrdinal()
  .domain(Object.keys(keyColors))
  .range(Object.values(keyColors))
  .unknown("#fff");

// Compute the position of each group on the pie:
const pieChart = d3.pie().value(function (d) {
  return d.value;
});

const data_ready = pieChart(reconstructedData);

// Build the pie chart: Basically, each part of the pie is a path that we build using the arc function.
svg
  .append("g")
  .attr(
    "transform",
    "translate(" + props.width / 2 + "," + props.height / 2 + ")"
  )
  .selectAll()
  .data(data_ready)
  .enter()
  .append("path")
  .attr(
    "d",
    d3
      .arc()
      .innerRadius(radius * 0.5) // This is the size of the donut hole
      .outerRadius(radius * 0.8)
  )
  .attr("fill", function (d) {
    return colorScale(d.data.key);
  })
  .attr("stroke", "black")
  .style("stroke-width", "1.5px")
  .style("opacity", 0.7);

onMounted(() => {
  donut.value.appendChild(svg.node());
});

onBeforeUnmount(() => {
  donut.value.removeChild(svg.node());
});
</script>
