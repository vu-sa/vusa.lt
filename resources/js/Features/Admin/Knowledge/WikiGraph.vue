<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
  type D3DragEvent,
  drag,
  forceCollide,
  forceLink,
  forceManyBody,
  forceSimulation,
  forceX,
  forceY,
  type Selection,
  select,
  type Simulation,
  type SimulationLinkDatum,
  type SimulationNodeDatum,
  zoom,
  type ZoomBehavior,
  zoomIdentity,
} from 'd3';
import { trans as $t, transChoice } from 'laravel-vue-i18n';
import { ArrowRight, Maximize, Minus, Plus, X } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';
import type { WikiGraphNode, WikiGraphPayload } from '@/Types/learning';

const props = withDefaults(defineProps<{
  graph: WikiGraphPayload;
  doneIds?: string[];
  /** Ids matching the page search; the rest fade. Null when nothing is searched. */
  matchIds?: Set<string> | null;
}>(), { doneIds: () => [], matchIds: null });

const selectedId = defineModel<string | null>('selected', { default: null });

interface Node extends SimulationNodeDatum, WikiGraphNode {}
interface Edge extends SimulationLinkDatum<Node> {
  source: string | Node;
  target: string | Node;
}

const { isReducedMotion } = useAccessibilityPreferences();
const wrapper = ref<HTMLDivElement | null>(null);
const svgRef = ref<SVGSVGElement | null>(null);

const done = computed(() => new Set(props.doneIds));
/** Topics take the category hues in their order; a term outside any topic stays ink. */
const sectionColors = computed(() => new Map(props.graph.sections.map((section, index) => [section.id, `var(--cat-${(index % 8) + 1})`])));
const colorFor = (node: WikiGraphNode): string => (node.sectionId && sectionColors.value.get(node.sectionId)) || 'var(--muted-foreground)';
const hasUnsorted = computed(() => props.graph.nodes.some(node => !node.sectionId || !sectionColors.value.has(node.sectionId)));
const hasLearned = computed(() => props.graph.nodes.some(node => done.value.has(node.id)));
const legend = computed(() => [
  ...props.graph.sections.map(section => ({ key: section.id, label: section.title, color: sectionColors.value.get(section.id) })),
  ...(hasUnsorted.value ? [{ key: 'unsorted', label: $t('learning.wiki.graph_unsorted'), color: 'var(--muted-foreground)' }] : []),
]);

const neighbours = computed(() => {
  const map = new Map<string, Set<string>>();
  for (const { source, target } of props.graph.edges) {
    map.set(source, (map.get(source) ?? new Set()).add(target));
    map.set(target, (map.get(target) ?? new Set()).add(source));
  }
  return map;
});

const nodesById = computed(() => new Map(props.graph.nodes.map(node => [node.id, node])));
const selected = computed(() => (selectedId.value ? nodesById.value.get(selectedId.value) ?? null : null));
const selectedNeighbours = computed(() => [...(selected.value ? neighbours.value.get(selected.value.id) ?? [] : [])]
  .map(id => nodesById.value.get(id))
  .filter((node): node is WikiGraphNode => Boolean(node))
  .sort((a, b) => a.title.localeCompare(b.title, 'lt')));
const selectedSection = computed(() => props.graph.sections.find(section => section.id === selected.value?.sectionId) ?? null);

const radius = (node: WikiGraphNode): number => 6 + Math.sqrt(node.degree) * 3.2;
const label = (node: WikiGraphNode): string => node.abbreviation || node.title;
/** Labels sit under the dot, so a term needs room for half its label on either side. */
const footprint = (node: WikiGraphNode): number => Math.max(radius(node) + 14, label(node).length * 3.4 + 6);
const LABEL_OFFSET = 14;
const endId = (end: string | Node): string => (typeof end === 'string' ? end : end.id);

let simulation: Simulation<Node, Edge> | null = null;
let zoomBehavior: ZoomBehavior<SVGSVGElement, unknown> | null = null;
let svgSelection: Selection<SVGSVGElement, unknown, null, undefined> | null = null;
let linkSelection: Selection<SVGLineElement, Edge, SVGGElement, unknown> | null = null;
let nodeSelection: Selection<SVGGElement, Node, SVGGElement, unknown> | null = null;
let simNodes: Node[] = [];
let resizeObserver: ResizeObserver | null = null;
let hoveredId: string | null = null;

/** Hover previews a term's neighbourhood; otherwise the selection, then the search, decide what stays lit. */
function paint() {
  if (!nodeSelection || !linkSelection) {
    return;
  }
  const focusId = hoveredId ?? selectedId.value;
  const focus = focusId ? new Set([focusId, ...(neighbours.value.get(focusId) ?? [])]) : null;
  const lit = (id: string) => (focus ? focus.has(id) : !props.matchIds || props.matchIds.has(id));
  nodeSelection
    .style('opacity', d => (lit(d.id) ? 1 : 0.15))
    .classed('is-selected', d => d.id === selectedId.value);
  linkSelection.style('opacity', (d) => {
    if (focusId) {
      return endId(d.source) === focusId || endId(d.target) === focusId ? 0.9 : 0.06;
    }
    return props.matchIds && !(props.matchIds.has(endId(d.source)) && props.matchIds.has(endId(d.target))) ? 0.1 : 0.45;
  });
}

function updatePositions() {
  linkSelection
    ?.attr('x1', d => (d.source as Node).x ?? 0)
    .attr('y1', d => (d.source as Node).y ?? 0)
    .attr('x2', d => (d.target as Node).x ?? 0)
    .attr('y2', d => (d.target as Node).y ?? 0);
  nodeSelection?.attr('transform', d => `translate(${d.x ?? 0},${d.y ?? 0})`);
}

function toggleSelected(id: string) {
  selectedId.value = selectedId.value === id ? null : id;
}

function dragBehavior(sim: Simulation<Node, Edge>) {
  return drag<SVGGElement, Node>()
    .on('start', (event: D3DragEvent<SVGGElement, Node, Node>) => {
      if (!event.active && !isReducedMotion.value) {
        sim.alphaTarget(0.3).restart();
      }
      event.subject.fx = event.subject.x;
      event.subject.fy = event.subject.y;
    })
    .on('drag', (event: D3DragEvent<SVGGElement, Node, Node>) => {
      event.subject.fx = event.x;
      event.subject.fy = event.y;
      if (isReducedMotion.value) {
        event.subject.x = event.x;
        event.subject.y = event.y;
        updatePositions();
      }
    })
    .on('end', (event: D3DragEvent<SVGGElement, Node, Node>) => {
      if (!event.active) {
        sim.alphaTarget(0);
      }
      event.subject.fx = null;
      event.subject.fy = null;
    });
}

function render() {
  if (!svgRef.value || !wrapper.value) {
    return;
  }
  simulation?.stop();
  const width = wrapper.value.clientWidth || 800;
  const height = wrapper.value.clientHeight || 600;

  simNodes = props.graph.nodes.map(node => ({ ...node }));
  const edges: Edge[] = props.graph.edges.map(edge => ({ ...edge }));

  svgSelection = select(svgRef.value);
  svgSelection.selectAll('*').remove();
  svgSelection.attr('viewBox', [-width / 2, -height / 2, width, height].join(' '));
  const root = svgSelection.append('g').attr('class', 'zoom-root');

  linkSelection = root.append('g')
    .attr('stroke', 'var(--foreground)')
    .attr('stroke-width', 1.25)
    .selectAll<SVGLineElement, Edge>('line')
    .data(edges)
    .join('line');

  nodeSelection = root.append('g')
    .selectAll<SVGGElement, Node>('g')
    .data(simNodes, d => d.id)
    .join('g')
    .attr('class', 'wiki-graph-node cursor-pointer outline-none [&.is-selected_.wiki-graph-dot]:[stroke-width:2.5] [&.is-selected_.wiki-graph-dot]:[stroke:var(--foreground)] [&:focus-visible_.wiki-graph-dot]:[stroke-width:2.5] [&:focus-visible_.wiki-graph-dot]:[stroke:var(--foreground)]')
    .attr('data-node-id', d => d.id)
    .attr('tabindex', 0)
    .attr('role', 'button')
    .attr('aria-label', d => `${d.title}. ${transChoice('learning.wiki.graph_links', d.degree, { count: String(d.degree) })}`);

  // A transparent hit circle keeps small terms tappable (44px).
  nodeSelection.append('circle').attr('r', d => Math.max(22, radius(d) + 6)).attr('fill', 'transparent');
  nodeSelection.filter(d => done.value.has(d.id))
    .append('circle')
    .attr('r', d => radius(d) + 3.5)
    .attr('fill', 'none')
    .attr('stroke', d => colorFor(d))
    .attr('stroke-width', 1.5)
    .attr('data-learned', '');
  nodeSelection.append('circle')
    .attr('class', 'wiki-graph-dot')
    .attr('r', radius)
    .attr('fill', d => colorFor(d))
    .attr('stroke', 'var(--background)')
    .attr('stroke-width', 1.5);
  nodeSelection.append('text')
    .attr('text-anchor', 'middle')
    .attr('y', d => radius(d) + LABEL_OFFSET)
    .attr('paint-order', 'stroke')
    .attr('stroke', 'var(--background)')
    .attr('stroke-width', 3)
    .attr('fill', 'var(--foreground)')
    .attr('class', 'text-[12px] font-semibold select-none')
    .text(label);

  nodeSelection
    .on('mouseenter', (_event, d) => {
      hoveredId = d.id;
      paint();
    })
    .on('mouseleave', () => {
      hoveredId = null;
      paint();
    })
    .on('click', (event: MouseEvent, d) => {
      event.stopPropagation();
      toggleSelected(d.id);
    })
    .on('keydown', (event: KeyboardEvent, d) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggleSelected(d.id);
      }
    });

  svgSelection.on('click', () => {
    selectedId.value = null;
  });

  simulation = forceSimulation<Node, Edge>(simNodes)
    .force('link', forceLink<Node, Edge>(edges).id(d => d.id).distance(90).strength(0.5))
    .force('charge', forceManyBody().strength(-320))
    .force('collide', forceCollide<Node>().radius(footprint).iterations(3))
    .force('x', forceX().strength(0.06))
    .force('y', forceY().strength(0.06 * (width / Math.max(height, 1))));
  nodeSelection.call(dragBehavior(simulation));
  // Settle most of the layout before the first paint so the view can fit it; the rest animates.
  simulation.stop().tick(isReducedMotion.value ? 300 : 150);
  updatePositions();
  simulation.on('tick', updatePositions);
  if (!isReducedMotion.value) {
    simulation.alpha(0.1).restart();
  }

  zoomBehavior = zoom<SVGSVGElement, unknown>()
    .scaleExtent([0.25, 4])
    .on('zoom', (event) => {
      root.attr('transform', event.transform.toString());
    });
  svgSelection.call(zoomBehavior).on('dblclick.zoom', null);
  svgSelection.call(zoomBehavior.transform, fitTransform());
  paint();
}

/** The zoom that shows every term, labels included, without enlarging a small graph past 1.15×. */
function fitTransform() {
  const width = wrapper.value?.clientWidth || 800;
  const height = wrapper.value?.clientHeight || 600;
  if (!simNodes.length) {
    return zoomIdentity;
  }
  const xs = simNodes.flatMap(node => [(node.x ?? 0) - footprint(node), (node.x ?? 0) + footprint(node)]);
  const ys = simNodes.flatMap(node => [(node.y ?? 0) - radius(node), (node.y ?? 0) + radius(node) + LABEL_OFFSET + 6]);
  const [minX, maxX, minY, maxY] = [Math.min(...xs), Math.max(...xs), Math.min(...ys), Math.max(...ys)];
  const scale = Math.min(1.15, 0.9 * Math.min(width / Math.max(maxX - minX, 1), height / Math.max(maxY - minY, 1)));

  return zoomIdentity.translate(-scale * (minX + maxX) / 2, -scale * (minY + maxY) / 2).scale(scale);
}

function transition() {
  return isReducedMotion.value || !svgSelection ? svgSelection : svgSelection.transition().duration(250);
}

function zoomBy(factor: number) {
  if (svgSelection && zoomBehavior) {
    svgSelection.interrupt();
    transition()?.call(zoomBehavior.scaleBy as never, factor);
  }
}

function resetZoom() {
  if (svgSelection && zoomBehavior) {
    svgSelection.interrupt();
    transition()?.call(zoomBehavior.transform as never, fitTransform());
  }
}

/** Brings a term into view, e.g. after picking it from search or the neighbour list. */
function centreOn(id: string) {
  const node = simNodes.find(candidate => candidate.id === id);
  if (svgSelection && zoomBehavior && node) {
    svgSelection.interrupt();
    transition()?.call(zoomBehavior.translateTo as never, node.x ?? 0, node.y ?? 0);
  }
}

defineExpose({ centreOn });

watch(selectedId, paint);
watch(() => props.matchIds, paint);
watch(() => [props.graph, props.doneIds], render, { deep: true });
watch(isReducedMotion, (reduced) => {
  if (reduced) {
    svgSelection?.interrupt();
    simulation?.stop().alphaTarget(0).tick(300);
    updatePositions();
  }
});

onMounted(() => {
  render();
  resizeObserver = new ResizeObserver(() => {
    if (wrapper.value && svgSelection) {
      const width = wrapper.value.clientWidth || 800;
      const height = wrapper.value.clientHeight || 600;
      svgSelection.attr('viewBox', [-width / 2, -height / 2, width, height].join(' '));
    }
  });
  if (wrapper.value) {
    resizeObserver.observe(wrapper.value);
  }
});

onBeforeUnmount(() => {
  simulation?.stop();
  resizeObserver?.disconnect();
});

function openNeighbour(id: string) {
  selectedId.value = id;
  centreOn(id);
}
</script>

<template>
  <div class="flex flex-col gap-3" data-slot="wiki-graph">
    <div ref="wrapper" class="relative h-[calc(100dvh-16rem)] min-h-[440px] w-full overflow-hidden border border-border bg-secondary/30">
      <svg ref="svgRef" class="size-full cursor-grab touch-none active:cursor-grabbing" :aria-label="$t('learning.wiki.graph_canvas')" role="group" />

      <div class="absolute top-3 right-3 flex flex-col border border-border bg-background/95 backdrop-blur">
        <button type="button" :aria-label="$t('learning.wiki.graph_zoom_in')" :title="$t('learning.wiki.graph_zoom_in')" class="flex size-9 items-center justify-center text-foreground hover:bg-accent pointer-coarse:size-11" @click="zoomBy(1.3)">
          <Plus class="size-4" aria-hidden="true" />
        </button>
        <button type="button" :aria-label="$t('learning.wiki.graph_zoom_out')" :title="$t('learning.wiki.graph_zoom_out')" class="flex size-9 items-center justify-center border-t border-border text-foreground hover:bg-accent pointer-coarse:size-11" @click="zoomBy(1 / 1.3)">
          <Minus class="size-4" aria-hidden="true" />
        </button>
        <button type="button" :aria-label="$t('learning.wiki.graph_reset')" :title="$t('learning.wiki.graph_reset')" class="flex size-9 items-center justify-center border-t border-border text-foreground hover:bg-accent pointer-coarse:size-11" @click="resetZoom">
          <Maximize class="size-4" aria-hidden="true" />
        </button>
      </div>

      <aside
        v-if="selected"
        class="absolute inset-x-0 bottom-0 max-h-[60%] overflow-y-auto border-t border-border bg-background p-4 sm:inset-x-auto sm:right-3 sm:bottom-3 sm:w-80 sm:border"
        data-slot="wiki-graph-selected"
        :aria-label="selected.title"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p v-if="selectedSection" class="text-xs font-bold tracking-[0.12em] uppercase" :style="{ color: sectionColors.get(selectedSection.id) }">
              {{ selectedSection.title }}
            </p>
            <h2 class="mt-1 text-base leading-snug font-semibold text-foreground">
              {{ selected.title }}
              <span v-if="selected.abbreviation" class="font-normal text-muted-foreground">({{ selected.abbreviation }})</span>
            </h2>
          </div>
          <button type="button" class="-mt-1 -mr-1 flex size-9 shrink-0 items-center justify-center text-muted-foreground hover:text-foreground pointer-coarse:size-11" :aria-label="$t('learning.wiki.graph_close')" @click="selectedId = null">
            <X class="size-4" aria-hidden="true" />
          </button>
        </div>
        <p v-if="selected.summary" class="mt-2 text-sm text-muted-foreground">
          {{ selected.summary }}
        </p>
        <p class="mt-3 text-xs font-medium text-muted-foreground">
          {{ transChoice('learning.wiki.graph_links', selected.degree, { count: String(selected.degree) }) }}
        </p>
        <ul v-if="selectedNeighbours.length" class="mt-1.5 flex flex-wrap gap-1.5">
          <li v-for="neighbour in selectedNeighbours" :key="neighbour.id">
            <button type="button" class="inline-flex min-h-8 items-center gap-1.5 border border-border px-2 text-xs text-foreground hover:border-foreground pointer-coarse:min-h-11" @click="openNeighbour(neighbour.id)">
              <span class="size-2 shrink-0 rounded-full" :style="{ backgroundColor: colorFor(neighbour) }" aria-hidden="true" />
              {{ label(neighbour) }}
            </button>
          </li>
        </ul>
        <Button as-child class="mt-4 w-full" voice="sentence">
          <Link :href="selected.url">
            {{ $t('learning.wiki.graph_open') }}
            <ArrowRight class="size-4" aria-hidden="true" />
          </Link>
        </Button>
      </aside>
    </div>
    <ul class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-muted-foreground" :aria-label="$t('learning.wiki.graph_sections')" data-slot="wiki-graph-legend">
      <li v-for="entry in legend" :key="entry.key" class="flex items-center gap-2">
        <span class="size-2.5 shrink-0 rounded-full" :style="{ backgroundColor: entry.color }" aria-hidden="true" />
        {{ entry.label }}
      </li>
      <li v-if="hasLearned" class="flex items-center gap-2">
        <span class="size-3 shrink-0 rounded-full border-[1.5px] border-muted-foreground" aria-hidden="true" />
        {{ $t('learning.wiki.learned') }}
      </li>
    </ul>
  </div>
</template>

