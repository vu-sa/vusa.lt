<template>
  <div
    ref="wrapper"
    class="relative h-[calc(100vh-13rem)] min-h-[480px] w-full overflow-hidden rounded-xl border bg-zinc-50/60 dark:bg-zinc-900/40"
  >
    <svg ref="svgRef" class="size-full cursor-grab active:cursor-grabbing" />

    <!-- Mode toggle -->
    <div
      v-if="hasTypeMode"
      class="absolute left-3 top-3 flex gap-0.5 rounded-lg border bg-background/90 p-1 text-xs font-medium shadow-sm backdrop-blur"
    >
      <button
        type="button"
        class="rounded-md px-2.5 py-1 transition-colors"
        :class="mode === 'institutions' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
        @click="setMode('institutions')"
      >
        {{ $t('Institucijos') }}
      </button>
      <button
        type="button"
        class="rounded-md px-2.5 py-1 transition-colors"
        :class="mode === 'types' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
        @click="setMode('types')"
      >
        {{ $t('Tipai') }}
      </button>
    </div>

    <!-- Zoom controls -->
    <div class="absolute right-3 top-3 flex flex-col gap-1">
      <button
        type="button"
        :title="$t('Priartinti')"
        class="flex size-8 items-center justify-center rounded-md border bg-background/90 text-foreground shadow-sm backdrop-blur transition-colors hover:bg-accent"
        @click="zoomBy(1.3)"
      >
        <Plus class="size-4" />
      </button>
      <button
        type="button"
        :title="$t('Atitolinti')"
        class="flex size-8 items-center justify-center rounded-md border bg-background/90 text-foreground shadow-sm backdrop-blur transition-colors hover:bg-accent"
        @click="zoomBy(1 / 1.3)"
      >
        <Minus class="size-4" />
      </button>
      <button
        type="button"
        :title="$t('Atstatyti vaizdą')"
        class="flex size-8 items-center justify-center rounded-md border bg-background/90 text-foreground shadow-sm backdrop-blur transition-colors hover:bg-accent"
        @click="resetZoom"
      >
        <Maximize class="size-4" />
      </button>
    </div>

    <!-- Legend -->
    <div class="absolute bottom-3 left-3 rounded-lg border bg-background/90 p-3 text-xs shadow-sm backdrop-blur">
      <p class="mb-1.5 font-semibold text-muted-foreground">{{ $t(legendTitle) }}</p>
      <ul class="space-y-1">
        <li v-for="entry in legendEntries" :key="entry.key" class="flex items-center gap-2">
          <span class="inline-block h-0.5 w-5 rounded-full" :style="{ backgroundColor: entry.color }" />
          <span>{{ $t(entry.labelKey) }}</span>
        </li>
      </ul>
      <div class="mt-2 flex items-center gap-2 border-t pt-2 text-muted-foreground">
        <ArrowRight class="size-3.5" />
        <span>{{ $t('relationships.graph.directional') }}</span>
        <ArrowLeftRight class="ml-2 size-3.5" />
        <span>{{ $t('relationships.graph.bidirectional') }}</span>
      </div>
    </div>

    <!-- Tooltip -->
    <div
      v-if="tooltip.visible"
      class="pointer-events-none absolute z-10 max-w-xs rounded-md border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-md"
      :style="{ left: `${tooltip.x}px`, top: `${tooltip.y}px` }"
    >
      <p v-if="tooltip.title" class="font-semibold">{{ tooltip.title }}</p>
      <p class="text-muted-foreground">{{ tooltip.subtitle }}</p>
      <p v-if="tooltip.description" class="mt-1 whitespace-normal text-muted-foreground">{{ tooltip.description }}</p>
      <p v-if="tooltip.bidirectional" class="mt-0.5 flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
        <ArrowLeftRight class="size-3" /> {{ $t('relationships.graph.bidirectional') }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import {
  type D3DragEvent,
  drag,
  forceCenter,
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
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowLeftRight, ArrowRight, Maximize, Minus, Plus } from 'lucide-vue-next';

import { EDGE_TYPE_STYLES, edgeTypeColor, type RelationshipType, type RelationshipScope, SCOPE_STYLES, scopeColor } from './relationshipColors';

type GraphMode = 'institutions' | 'types';

interface GraphNode extends SimulationNodeDatum {
  id: string;
  label: string;
  userCount: number;
}

interface GraphEdge extends SimulationLinkDatum<GraphNode> {
  source: string | GraphNode;
  target: string | GraphNode;
  direction: string;
  type: string;
  scope: string;
  bidirectional: boolean;
  relationship_name: string | null;
  relationship_description: string | null;
}

const props = defineProps<{
  institutions: Record<string, any>[];
  institutionRelationships: Record<string, any>[];
  types?: Record<string, any>[];
  typeRelationships?: Record<string, any>[];
}>();

const wrapper = ref<HTMLDivElement | null>(null);
const svgRef = ref<SVGSVGElement | null>(null);

/** 'institutions' shows concrete institutions; 'types' shows the type-to-type relations. */
const mode = ref<GraphMode>('institutions');
const hasTypeMode = computed(() => (props.types?.length ?? 0) > 0);

const activeNodesRaw = computed(() => (mode.value === 'types' ? (props.types ?? []) : props.institutions));
const activeEdgesRaw = computed(() => (mode.value === 'types' ? (props.typeRelationships ?? []) : props.institutionRelationships));

function setMode(next: GraphMode) {
  if (mode.value === next) {
    return;
  }
  mode.value = next;
}

const tooltip = reactive<{
  visible: boolean;
  x: number;
  y: number;
  title: string;
  subtitle: string;
  description: string;
  bidirectional: boolean;
}>({ visible: false, x: 0, y: 0, title: '', subtitle: '', description: '', bidirectional: false });

/**
 * Legend entries: in institutions mode keyed by relationship type, in types mode
 * keyed by scope (within-tenant / cross-tenant). Only values present are shown.
 */
const legendEntries = computed(() => {
  if (mode.value === 'types') {
    const present = new Set(activeEdgesRaw.value.map(r => r.scope));
    return (Object.keys(SCOPE_STYLES) as RelationshipScope[])
      .filter(scope => present.has(scope))
      .map(scope => ({ key: scope, ...SCOPE_STYLES[scope] }));
  }
  const present = new Set(activeEdgesRaw.value.map(r => r.type));
  return (Object.keys(EDGE_TYPE_STYLES) as RelationshipType[])
    .filter(type => present.has(type))
    .map(type => ({ key: type, ...EDGE_TYPE_STYLES[type] }));
});

const legendTitle = computed(() =>
  mode.value === 'types' ? 'relationships.graph.scope_legend_title' : 'relationships.graph.legend_title',
);

function resolveName(name: unknown): string {
  if (typeof name === 'string') {
    return name;
  }
  if (name && typeof name === 'object') {
    const record = name as Record<string, string>;
    return record.lt ?? record.en ?? Object.values(record)[0] ?? '';
  }
  return '';
}

function buildNodes(): GraphNode[] {
  return activeNodesRaw.value.map(item => ({
    id: String(item.id),
    label: resolveName(item.name),
    userCount: Number(item.users_count ?? item.institutions_count ?? 0),
  }));
}

function buildEdges(nodeIds: Set<string>): GraphEdge[] {
  return activeEdgesRaw.value
    .map(relationship => ({
      source: String(relationship.source ?? relationship.relationshipable_id),
      target: String(relationship.target ?? relationship.related_model_id),
      direction: relationship.direction ?? 'outgoing',
      type: relationship.type ?? 'direct',
      scope: relationship.scope ?? 'within-tenant',
      bidirectional: Boolean(relationship.bidirectional),
      relationship_name: relationship.relationship_name ?? null,
      relationship_description: relationship.relationship_description ?? null,
    }))
    // Drop dangling edges that reference an institution not in the node set.
    .filter(edge => nodeIds.has(edge.source as string) && nodeIds.has(edge.target as string));
}

function nodeRadius(node: GraphNode): number {
  return 7 + Math.sqrt(node.userCount) * 2.2;
}

/** Institutions mode colors edges by relationship type; types mode by scope. */
function colorForEdge(edge: GraphEdge): string {
  return mode.value === 'types' ? scopeColor(edge.scope) : edgeTypeColor(edge.type);
}

function edgeId(node: string | GraphNode): string {
  return typeof node === 'string' ? node : node.id;
}

/** Extra clearance (px) between a node edge and an arrowhead so the arrow stays visible. */
const ARROW_GAP = 4;

// ---- d3 state (created in onMounted) -------------------------------------
let simulation: Simulation<GraphNode, GraphEdge> | null = null;
let zoomBehavior: ZoomBehavior<SVGSVGElement, unknown> | null = null;
let svgSelection: Selection<SVGSVGElement, unknown, null, undefined> | null = null;
let linkSelection: Selection<SVGGElement, GraphEdge, SVGGElement, unknown> | null = null;
let nodeSelection: Selection<SVGGElement, GraphNode, SVGGElement, unknown> | null = null;
let resizeObserver: ResizeObserver | null = null;

let selectedEdge: GraphEdge | null = null;

/** Adjacency for neighbor highlighting. */
let adjacency = new Map<string, Set<string>>();

function buildAdjacency(edges: GraphEdge[]) {
  adjacency = new Map();
  for (const edge of edges) {
    const s = edgeId(edge.source);
    const t = edgeId(edge.target);
    if (!adjacency.has(s)) {
      adjacency.set(s, new Set());
    }
    if (!adjacency.has(t)) {
      adjacency.set(t, new Set());
    }
    adjacency.get(s)!.add(t);
    adjacency.get(t)!.add(s);
  }
}

function clearHighlight() {
  if (!linkSelection || !nodeSelection) {
    return;
  }
  linkSelection.style('opacity', 1);
  nodeSelection.style('opacity', 1);
}

function highlightNode(node: GraphNode) {
  if (!linkSelection || !nodeSelection) {
    return;
  }
  const neighbors = adjacency.get(node.id) ?? new Set<string>();
  nodeSelection.style('opacity', d => (d.id === node.id || neighbors.has(d.id) ? 1 : 0.12));
  linkSelection.style('opacity', d =>
    edgeId(d.source) === node.id || edgeId(d.target) === node.id ? 1 : 0.08,
  );
}

function highlightEdge(edge: GraphEdge) {
  if (!linkSelection || !nodeSelection) {
    return;
  }
  const s = edgeId(edge.source);
  const t = edgeId(edge.target);
  nodeSelection.style('opacity', d => (d.id === s || d.id === t ? 1 : 0.12));
  linkSelection.style('opacity', d => (d === edge ? 1 : 0.08));
}

function showEdgeTooltip(event: MouseEvent, edge: GraphEdge) {
  const bounds = wrapper.value?.getBoundingClientRect();
  if (!bounds) {
    return;
  }
  const scopeKey = edge.scope === 'cross-tenant'
    ? 'relationships.graph.scope_cross_tenant'
    : 'relationships.graph.scope_within_tenant';
  if (mode.value === 'types') {
    tooltip.title = edge.relationship_name ?? $t('relationships.graph.type_type_based');
    tooltip.subtitle = $t(scopeKey);
  } else {
    const directionKey = edge.direction === 'sibling'
      ? 'relationships.graph.direction_sibling'
      : 'relationships.graph.direction_outgoing';
    tooltip.title = edge.relationship_name ?? $t(EDGE_TYPE_STYLES[edge.type as RelationshipType]?.labelKey ?? 'relationships.graph.type_direct');
    tooltip.subtitle = `${$t(directionKey)} · ${$t(scopeKey)}`;
  }
  tooltip.description = edge.relationship_description ?? '';
  tooltip.bidirectional = edge.bidirectional;
  tooltip.x = event.clientX - bounds.left + 12;
  tooltip.y = event.clientY - bounds.top + 12;
  tooltip.visible = true;
}

function hideTooltip() {
  tooltip.visible = false;
}

function dragBehavior(sim: Simulation<GraphNode, GraphEdge>) {
  return drag<SVGGElement, GraphNode>()
    .on('start', (event: D3DragEvent<SVGGElement, GraphNode, GraphNode>) => {
      if (!event.active) {
        sim.alphaTarget(0.3).restart();
      }
      event.subject.fx = event.subject.x;
      event.subject.fy = event.subject.y;
    })
    .on('drag', (event: D3DragEvent<SVGGElement, GraphNode, GraphNode>) => {
      event.subject.fx = event.x;
      event.subject.fy = event.y;
    })
    .on('end', (event: D3DragEvent<SVGGElement, GraphNode, GraphNode>) => {
      if (!event.active) {
        sim.alphaTarget(0);
      }
      event.subject.fx = null;
      event.subject.fy = null;
    });
}

function setupMarkers(defs: Selection<SVGDefsElement, unknown, null, undefined>) {
  // userSpaceOnUse keeps arrow size constant (independent of stroke width); refX
  // anchors the tip at the line endpoint, which the tick shortens to the node edge.
  const markers = [
    { id: 'graph-arrow-end', refX: 9, path: 'M0,-4L9,0L0,4' },
    { id: 'graph-arrow-start', refX: 0, path: 'M9,-4L0,0L9,4' },
  ];
  for (const marker of markers) {
    defs.append('marker')
      .attr('id', marker.id)
      .attr('viewBox', '0 -5 10 10')
      .attr('refX', marker.refX)
      .attr('refY', 0)
      .attr('markerWidth', 9)
      .attr('markerHeight', 9)
      .attr('markerUnits', 'userSpaceOnUse')
      .attr('orient', 'auto')
      .append('path')
      .attr('d', marker.path)
      .attr('fill', 'context-stroke');
  }
}

function render() {
  if (!svgRef.value || !wrapper.value) {
    return;
  }

  const width = wrapper.value.clientWidth || 800;
  const height = wrapper.value.clientHeight || 600;

  const nodes = buildNodes();
  const nodeIds = new Set(nodes.map(n => n.id));
  const edges = buildEdges(nodeIds);
  buildAdjacency(edges);

  svgSelection = select(svgRef.value);
  svgSelection.selectAll('*').remove();
  selectedEdge = null;
  hideTooltip();

  svgSelection
    .attr('viewBox', [-width / 2, -height / 2, width, height].join(' '))
    .attr('preserveAspectRatio', 'xMidYMid meet');

  const defs = svgSelection.append('defs');
  setupMarkers(defs);

  const root = svgSelection.append('g').attr('class', 'zoom-root');

  // Edge groups: a visible line + a wide transparent hit area for easy hovering.
  const linkGroup = root.append('g').attr('fill', 'none');
  linkSelection = linkGroup
    .selectAll<SVGGElement, GraphEdge>('g')
    .data(edges)
    .join('g');

  linkSelection
    .append('line')
    .attr('class', 'graph-edge-visible')
    .attr('stroke', d => colorForEdge(d))
    .attr('stroke-width', 1.8)
    .attr('stroke-opacity', 0.7)
    .attr('stroke-dasharray', d => (d.direction === 'sibling' ? '4 3' : null))
    .attr('marker-end', d => (d.direction === 'sibling' ? null : 'url(#graph-arrow-end)'))
    .attr('marker-start', d => (d.bidirectional ? 'url(#graph-arrow-start)' : null));

  linkSelection
    .append('line')
    .attr('class', 'graph-edge-hit')
    .attr('stroke', 'transparent')
    .attr('stroke-width', 14)
    .style('cursor', 'pointer')
    .on('mouseenter', (event: MouseEvent, d) => {
      if (!selectedEdge) {
        highlightEdge(d);
      }
      showEdgeTooltip(event, d);
    })
    .on('mousemove', (event: MouseEvent, d) => showEdgeTooltip(event, d))
    .on('mouseleave', () => {
      hideTooltip();
      if (!selectedEdge) {
        clearHighlight();
      }
    })
    .on('click', (event: MouseEvent, d) => {
      event.stopPropagation();
      if (selectedEdge === d) {
        selectedEdge = null;
        clearHighlight();
      } else {
        selectedEdge = d;
        highlightEdge(d);
      }
    });

  // Node groups
  nodeSelection = root
    .append('g')
    .selectAll<SVGGElement, GraphNode>('g')
    .data(nodes)
    .join('g')
    .style('cursor', 'pointer');

  nodeSelection
    .append('circle')
    .attr('r', nodeRadius)
    .attr('stroke-width', 1.5)
    .attr('class', 'fill-vusa-red stroke-white dark:fill-vusa-yellow dark:stroke-zinc-900');

  nodeSelection
    .append('text')
    .attr('x', d => nodeRadius(d) + 4)
    .attr('y', '0.31em')
    .attr('paint-order', 'stroke')
    .attr('stroke-width', 3)
    .attr('class', 'text-[13px] font-semibold fill-zinc-800 stroke-zinc-50 dark:fill-zinc-100 dark:stroke-zinc-900')
    .text(d => d.label);

  nodeSelection
    .on('mouseenter', (_event: MouseEvent, d) => {
      if (!selectedEdge) {
        highlightNode(d);
      }
    })
    .on('mouseleave', () => {
      if (!selectedEdge) {
        clearHighlight();
      }
    })
    .on('dblclick', (event: MouseEvent, d) => {
      event.stopPropagation();
      const routeName = mode.value === 'types' ? 'types.edit' : 'institutions.edit';
      window.open(route(routeName, d.id), '_blank');
    });

  // Clear sticky selection when clicking empty canvas.
  svgSelection.on('click', () => {
    if (selectedEdge) {
      selectedEdge = null;
      clearHighlight();
    }
  });

  // Simulation
  simulation = forceSimulation<GraphNode, GraphEdge>(nodes)
    .force('link', forceLink<GraphNode, GraphEdge>(edges).id(d => d.id).distance(110).strength(0.4))
    .force('charge', forceManyBody().strength(-380))
    .force('center', forceCenter(0, 0))
    .force('collide', forceCollide<GraphNode>().radius(d => nodeRadius(d) + 22).iterations(2))
    .force('x', forceX().strength(0.04))
    .force('y', forceY().strength(0.04));

  nodeSelection.call(dragBehavior(simulation));

  simulation.on('tick', () => {
    // Shorten each edge to the node circle edges so arrowheads aren't hidden under nodes.
    linkSelection?.each(function (this: SVGGElement, d: GraphEdge) {
      const source = d.source as GraphNode;
      const target = d.target as GraphNode;
      const sx = source.x ?? 0;
      const sy = source.y ?? 0;
      const tx = target.x ?? 0;
      const ty = target.y ?? 0;
      const dist = Math.hypot(tx - sx, ty - sy) || 1;
      const ux = (tx - sx) / dist;
      const uy = (ty - sy) / dist;
      const sourcePad = nodeRadius(source) + (d.bidirectional ? ARROW_GAP : 1);
      const targetPad = nodeRadius(target) + (d.direction === 'sibling' ? 1 : ARROW_GAP);
      select(this)
        .selectAll<SVGLineElement, GraphEdge>('line')
        .attr('x1', sx + ux * sourcePad)
        .attr('y1', sy + uy * sourcePad)
        .attr('x2', tx - ux * targetPad)
        .attr('y2', ty - uy * targetPad);
    });

    nodeSelection?.attr('transform', d => `translate(${d.x ?? 0},${d.y ?? 0})`);
  });

  // Zoom / pan
  zoomBehavior = zoom<SVGSVGElement, unknown>()
    .scaleExtent([0.2, 4])
    .filter((event) => {
      // Allow wheel + drag-pan, but not double-click (reserved for opening the node).
      return event.type !== 'dblclick';
    })
    .on('zoom', (event) => {
      root.attr('transform', event.transform.toString());
    });

  svgSelection.call(zoomBehavior);
  svgSelection.on('dblclick.zoom', null);
}

function zoomBy(factor: number) {
  if (svgSelection && zoomBehavior) {
    svgSelection.transition().duration(200).call(zoomBehavior.scaleBy, factor);
  }
}

function resetZoom() {
  if (svgSelection && zoomBehavior) {
    svgSelection.transition().duration(300).call(zoomBehavior.transform, zoomIdentity);
  }
}

onMounted(() => {
  render();

  resizeObserver = new ResizeObserver(() => {
    if (!svgRef.value || !wrapper.value || !simulation) {
      return;
    }
    const width = wrapper.value.clientWidth || 800;
    const height = wrapper.value.clientHeight || 600;
    svgSelection?.attr('viewBox', [-width / 2, -height / 2, width, height].join(' '));
  });
  if (wrapper.value) {
    resizeObserver.observe(wrapper.value);
  }
});

watch(
  () => [mode.value, props.institutions, props.institutionRelationships, props.types, props.typeRelationships],
  () => {
    simulation?.stop();
    render();
  },
  { deep: true },
);

onBeforeUnmount(() => {
  simulation?.stop();
  resizeObserver?.disconnect();
  resizeObserver = null;
});
</script>
