<template>
  <div ref="graph"></div>
</template>

<script setup lang="ts">
// import * as d3 from "d3";
import { computed } from "vue";
import {
  create,
  drag,
  forceLink,
  forceManyBody,
  forceSimulation,
  forceX,
  forceY,
  select,
} from "d3";
import { onBeforeUnmount, onMounted, ref } from "vue";

const props = defineProps<{
  institutions: Record<string, any>[];
  institutionRelationships: Record<string, any>[];
}>();

const d3 = {
  create,
  drag,
  select,
  forceSimulation,
  forceLink,
  forceX,
  forceY,
  forceManyBody,
};

const nodes = computed(() => {
  return props.institutions.map((institution) => {
    return {
      id: institution.id,
      label: institution.name,
      userCount: institution.users_count,
      // short_name: institution.short_name,
      // alias: institution.alias,
    };
  });
});

const edges = computed(() => {
  return props.institutionRelationships.map((relationship) => {
    return {
      source: relationship.relationshipable_id,
      target: relationship.related_model_id,
    };
  });
});

const width = 1920;
const height = 1600;

// define drag function
const graphDrag = (simulation) => {
  function dragstarted(event) {
    if (!event.active) simulation.alphaTarget(0.3).restart();
    event.subject.fx = event.subject.x;
    event.subject.fy = event.subject.y;
  }

  function dragged(event) {
    event.subject.fx = event.x;
    event.subject.fy = event.y;
  }

  function dragended(event) {
    if (!event.active) simulation.alphaTarget(0);
    event.subject.fx = null;
    event.subject.fy = null;
  }

  return d3
    .drag()
    .on("start", dragstarted)
    .on("drag", dragged)
    .on("end", dragended);
};

const simulation = d3
  .forceSimulation(nodes.value)
  .force(
    "link",
    d3.forceLink(edges.value).id((d) => d.id)
  )
  .force("charge", d3.forceManyBody().strength(-400))
  .force("x", d3.forceX())
  .force("y", d3.forceY());

const svg = d3
  .create("svg")
  .attr("viewBox", [-width / 2, -height / 2, width, height]);

const link = svg
  .append("g")
  .attr("stroke", "#999")
  .attr("stroke-opacity", 0.6)
  .attr("stroke-width", 3)
  .selectAll("line")
  .data(edges.value)
  .join("line")
  .attr("stroke-width", (d) => Math.sqrt(d.value));

const node = svg
  .append("g")
  .attr("stroke", "#fff")
  .attr("id", "nodes")
  .attr("stroke-width", 1.5)
  .selectAll("g")
  .data(nodes.value)
  .join("g")
  // create circles
  .call(graphDrag(simulation));

const circles = node
  .append("circle")
  .attr("id", (d) => d.id)
  .attr("r", (d) => {
    return 6 + d.userCount * 0.5;
  })
  .attr("class", "fill-vusa-red dark:fill-vusa-yellow");

node.append("title").text((d) => d.id);

// add labels near the nodes
node
  .append("text")
  .attr("x", 8)
  .attr("y", "0.25em")
  .attr("stroke-width", "0")
  .attr(
    "class",
    "text-base font-bold fill-gray-800 dark:fill-gray-200 stroke-gray-200 dark:stroke-gray-800"
  )
  .text((d) => d.label);

simulation.on("tick", () => {
  link
    .attr("x1", (d) => d.source.x)
    .attr("y1", (d) => d.source.y)
    .attr("x2", (d) => d.target.x)
    .attr("y2", (d) => d.target.y);

  node.attr("transform", (d) => `translate(${d.x},${d.y})`);
});

// on circle doubleclick, open the node
circles.on("dblclick", function (d) {
  // get the id of the node from target id
  const id = d3.select(this).attr("id");

  // open
  window.open(route("institutions.edit", id), "_blank");
});

const graph = ref(null);

onMounted(() => {
  graph.value.appendChild(svg.node());
});

onBeforeUnmount(() => {
  graph.value.removeChild(svg.node());
});
</script>
