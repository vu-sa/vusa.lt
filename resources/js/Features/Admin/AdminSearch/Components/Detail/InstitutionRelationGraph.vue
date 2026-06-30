<template>
  <div ref="root" class="relative">
    <svg
      :viewBox="`0 0 ${layout.size} ${layout.size}`"
      class="mx-auto block w-full"
      :style="{ maxWidth: `${layout.size}px` }"
    >
      <defs>
        <marker
          v-for="m in markerDefs"
          :id="`rel-arrow-${m.dir}`"
          :key="m.dir"
          viewBox="0 -5 10 10"
          :refX="m.refX"
          :refY="0"
          markerWidth="9"
          markerHeight="9"
          markerUnits="userSpaceOnUse"
          orient="auto"
        >
          <path :d="m.path" :fill="m.color" />
        </marker>
      </defs>

      <!-- Edges -->
      <line
        v-for="edge in edges"
        :key="`edge-${edge.id}`"
        :x1="edge.x1"
        :y1="edge.y1"
        :x2="edge.x2"
        :y2="edge.y2"
        :stroke="edge.color"
        stroke-width="1.4"
        :stroke-dasharray="edge.direction === 'sibling' ? '4 3' : undefined"
        stroke-opacity="0.5"
        :marker-end="edge.arrow === 'out' ? `url(#rel-arrow-${edge.direction})` : undefined"
        :marker-start="edge.arrow === 'in' ? `url(#rel-arrow-${edge.direction})` : undefined"
      />

      <!-- Wide transparent hit areas for edge hover tooltips -->
      <line
        v-for="edge in edges"
        :key="`hit-${edge.id}`"
        :x1="edge.hx1"
        :y1="edge.hy1"
        :x2="edge.hx2"
        :y2="edge.hy2"
        stroke="transparent"
        stroke-width="14"
        class="cursor-pointer"
        @mouseenter="showEdgeTooltip($event, edge)"
        @mousemove="showEdgeTooltip($event, edge)"
        @mouseleave="hideTooltip"
      />

      <!-- Center node (this institution) -->
      <g>
        <title>{{ centerName }}</title>
        <circle
          :cx="layout.center.x"
          :cy="layout.center.y"
          :r="CENTER_R + 4"
          class="fill-vusa-red/10 dark:fill-vusa-yellow/10"
        />
        <circle
          :cx="layout.center.x"
          :cy="layout.center.y"
          :r="CENTER_R"
          class="fill-vusa-red stroke-white dark:fill-vusa-yellow dark:stroke-zinc-900"
          stroke-width="2.5"
        />
      </g>

      <!-- Related nodes -->
      <g
        v-for="node in nodes"
        :key="node.id"
        class="rel-leaf cursor-pointer"
        role="link"
        tabindex="0"
        @click="goTo(node.id)"
        @keydown.enter="goTo(node.id)"
      >
        <title>{{ node.name }} · {{ $t(node.labelKey) }}</title>
        <circle
          :cx="node.x"
          :cy="node.y"
          :r="LEAF_R"
          :stroke="node.color"
          stroke-width="2"
          class="fill-white dark:fill-zinc-800"
        />
        <g :transform="node.labelTransform">
          <text
            x="0"
            dy="0.32em"
            :text-anchor="node.labelAnchor"
            paint-order="stroke"
            stroke-width="3"
            class="text-[10px] font-medium fill-zinc-700 stroke-zinc-50 dark:fill-zinc-200 dark:stroke-zinc-900"
          >
            {{ truncate(node.name) }}
          </text>
        </g>
      </g>
    </svg>

    <!-- Edge hover tooltip -->
    <div
      v-if="tooltip.visible"
      class="pointer-events-none absolute z-10 max-w-[14rem] rounded-md border bg-popover px-2.5 py-1.5 text-xs text-popover-foreground shadow-md"
      :style="{ left: `${tooltip.x}px`, top: `${tooltip.y}px` }"
    >
      <p class="font-semibold">{{ tooltip.title }}</p>
      <p class="text-muted-foreground">{{ tooltip.subtitle }}</p>
      <p :class="tooltip.authorized ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'">
        {{ tooltip.authorized ? $t('relationships.authorized') : $t('relationships.not_authorized') }}
      </p>
    </div>

    <!-- Legend (directions present, with counts) -->
    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-muted-foreground">
      <span v-for="dir in usedDirections" :key="`legend-${dir}`" class="flex items-center gap-1">
        <span class="inline-block size-2 rounded-full" :style="{ backgroundColor: DIRECTION_STYLES[dir].color }" />
        {{ $t(DIRECTION_STYLES[dir].labelKey) }} ({{ directionCounts[dir] }})
      </span>
      <span v-if="overflow > 0" class="italic">
        {{ $t('relationships.graph.and_more', { count: overflow }) }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { DIRECTION_STYLES, directionStyle, EDGE_TYPE_STYLES, type RelationshipDirection, type RelationshipType } from '@/Components/Graphs/relationshipColors';

interface RelatedInstitution {
  id: string;
  name: string;
  direction: string;
  type: string;
  authorized: boolean;
}

const props = defineProps<{
  centerName: string;
  related: RelatedInstitution[];
}>();

const CENTER_R = 16;
const LEAF_R = 10;
/** Beyond this, the ring stays legible only by capping and showing a "+N" note. */
const MAX_NODES = 16;
const DIRECTION_ORDER: Record<string, number> = { outgoing: 0, incoming: 1, sibling: 2 };

function dirKey(direction: string): RelationshipDirection {
  return direction in DIRECTION_STYLES ? (direction as RelationshipDirection) : 'sibling';
}

function truncate(name: string, max = 16): string {
  return name.length > max ? `${name.slice(0, max - 1)}…` : name;
}

/** Sorted so that same-direction nodes sit on a contiguous arc (colors cluster). */
const displayed = computed(() =>
  [...props.related]
    .sort((a, b) => (DIRECTION_ORDER[dirKey(a.direction)] ?? 9) - (DIRECTION_ORDER[dirKey(b.direction)] ?? 9))
    .slice(0, MAX_NODES),
);

const overflow = computed(() => props.related.length - displayed.value.length);

/** Geometry derived from how many nodes we draw — keeps spacing constant as count grows. */
const layout = computed(() => {
  const n = displayed.value.length;
  const minArc = 2 * LEAF_R + 10;
  const radius = Math.max(74, (n * minArc) / (2 * Math.PI));
  const labelAllowance = 96;
  const size = Math.round((radius + LEAF_R + labelAllowance) * 2);
  return { radius, size, center: { x: size / 2, y: size / 2 } };
});

const nodes = computed(() => {
  const { radius, center } = layout.value;
  const items = displayed.value;
  const n = items.length;

  // Contiguous direction groups, each given an angular span proportional to its size.
  const groups: Array<{ dir: RelationshipDirection; items: RelatedInstitution[] }> = [];
  for (const item of items) {
    const dir = dirKey(item.direction);
    const last = groups[groups.length - 1];
    if (last && last.dir === dir) {
      last.items.push(item);
    } else {
      groups.push({ dir, items: [item] });
    }
  }

  const gapDeg = groups.length > 1 ? 16 : 0;
  const availableDeg = 360 - gapDeg * groups.length;
  let cursor = -90 + gapDeg / 2;

  const result: Array<RelatedInstitution & {
    x: number;
    y: number;
    rad: number;
    color: string;
    labelKey: string;
    arrow: 'out' | 'in' | 'none';
    labelTransform: string;
    labelAnchor: 'start' | 'end';
  }> = [];

  for (const group of groups) {
    const span = availableDeg * (group.items.length / n);
    group.items.forEach((item, i) => {
      const frac = group.items.length === 1 ? 0.5 : (i + 0.5) / group.items.length;
      const deg = cursor + frac * span;
      const rad = (deg * Math.PI) / 180;
      const style = directionStyle(item.direction);
      const onLeft = Math.cos(rad) < 0;
      const labelRadius = radius + LEAF_R + 7;
      const labelTransform = `translate(${center.x},${center.y}) rotate(${deg}) translate(${labelRadius},0)${onLeft ? ' rotate(180)' : ''}`;
      result.push({
        ...item,
        x: center.x + radius * Math.cos(rad),
        y: center.y + radius * Math.sin(rad),
        rad,
        color: style.color,
        labelKey: style.labelKey,
        arrow: style.arrow,
        labelTransform,
        labelAnchor: onLeft ? 'end' : 'start',
      });
    });
    cursor += span + gapDeg;
  }

  return result;
});

const edges = computed(() => {
  const { center } = layout.value;
  return nodes.value.map((node) => {
    const dx = node.x - center.x;
    const dy = node.y - center.y;
    const dist = Math.hypot(dx, dy) || 1;
    const ux = dx / dist;
    const uy = dy / dist;
    return {
      id: node.id,
      name: node.name,
      type: node.type,
      authorized: node.authorized,
      directionKey: node.labelKey,
      direction: node.arrow === 'none' ? 'sibling' : dirKey(node.direction),
      arrow: node.arrow,
      color: node.color,
      // Offsets clear the center halo (CENTER_R + 4) and leaf circle so arrowheads show.
      x1: center.x + ux * (CENTER_R + 6),
      y1: center.y + uy * (CENTER_R + 6),
      x2: node.x - ux * (LEAF_R + 2),
      y2: node.y - uy * (LEAF_R + 2),
      // Full-length endpoints for the (wider) hover hit area.
      hx1: center.x,
      hy1: center.y,
      hx2: node.x,
      hy2: node.y,
    };
  });
});

const directionCounts = computed<Record<string, number>>(() => {
  const counts: Record<string, number> = {};
  for (const item of props.related) {
    const key = dirKey(item.direction);
    counts[key] = (counts[key] ?? 0) + 1;
  }
  return counts;
});

const usedDirections = computed<RelationshipDirection[]>(() => {
  const present = new Set<RelationshipDirection>(props.related.map(r => dirKey(r.direction)));
  return (Object.keys(DIRECTION_STYLES) as RelationshipDirection[]).filter(dir => present.has(dir));
});

/** Directions that actually draw an arrowhead (siblings are undirected). */
const arrowDirections = computed<RelationshipDirection[]>(() =>
  usedDirections.value.filter(dir => DIRECTION_STYLES[dir].arrow !== 'none'),
);

/**
 * One marker per arrowed direction. Outgoing arrows (marker-end) point toward the
 * leaf; incoming arrows (marker-start) point back toward the center — hence the
 * mirrored path and refX so the tip sits at the relevant line endpoint.
 */
const markerDefs = computed(() =>
  arrowDirections.value.map((dir) => {
    const style = DIRECTION_STYLES[dir];
    return style.arrow === 'in'
      ? { dir, color: style.color, refX: 0.5, path: 'M9,-4L0,0L9,4' }
      : { dir, color: style.color, refX: 8.5, path: 'M0,-4L9,0L0,4' };
  }),
);

const root = ref<HTMLDivElement | null>(null);

const tooltip = reactive<{
  visible: boolean;
  x: number;
  y: number;
  title: string;
  subtitle: string;
  authorized: boolean;
}>({ visible: false, x: 0, y: 0, title: '', subtitle: '', authorized: false });

function showEdgeTooltip(event: MouseEvent, edge: { name: string; directionKey: string; type: string; authorized: boolean }) {
  const bounds = root.value?.getBoundingClientRect();
  if (!bounds) {
    return;
  }
  const typeKey = EDGE_TYPE_STYLES[edge.type as RelationshipType]?.labelKey ?? 'relationships.graph.type_direct';
  tooltip.title = edge.name;
  tooltip.subtitle = `${$t(edge.directionKey)} · ${$t(typeKey)}`;
  tooltip.authorized = edge.authorized;
  tooltip.x = event.clientX - bounds.left + 12;
  tooltip.y = event.clientY - bounds.top + 12;
  tooltip.visible = true;
}

function hideTooltip() {
  tooltip.visible = false;
}

function goTo(id: string) {
  router.visit(route('institutions.show', id));
}
</script>

<style scoped>
.rel-leaf circle {
  transition: stroke-width 0.12s ease;
}
.rel-leaf:hover circle,
.rel-leaf:focus-visible circle {
  stroke-width: 3.5;
}
.rel-leaf:hover text {
  font-weight: 700;
}
</style>
