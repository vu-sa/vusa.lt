import * as d3 from 'd3';

import type { GanttColors } from '@/Components/Graphs/ganttColors';

import { toDateString } from '../composables/useDutiableTimelineData';
import { BAR_INSET } from './renderDutiableBars';
import type { BarDragState } from '../composables/useBarDrag';

export interface DragGhostContext {
  layer: d3.Selection<SVGGElement, unknown, null, undefined>;
  x: d3.ScaleTime<number, number>;
  colors: GanttColors;
  innerWidth: number;
  innerHeight: number;
  state: BarDragState | null;
  /** Lane geometry by row id, so the ghost sits exactly over the bar it replaces. */
  laneFor: (rowId: string) => { top: number; height: number } | undefined;
}

/**
 * The live preview during a drag.
 *
 * `render()` clears the whole SVG, so nothing here may depend on a full re-render: this
 * draws into a layer that survives one gesture and is redrawn imperatively on every
 * pointermove. The bar underneath keeps its saved position, so the ghost reads as
 * "this is where it will land" rather than as the move already having happened.
 */
export function renderDragGhost(ctx: DragGhostContext): void {
  const { layer, x, colors, innerWidth, innerHeight, state, laneFor } = ctx;

  layer.selectAll('*').remove();

  if (state === null || state.entries.length === 0) return;

  const positioned = state.entries.flatMap((entry) => {
    const lane = laneFor(entry.rowId);

    return lane ? [{ entry, lane }] : [];
  });

  layer.selectAll('rect.drag-ghost-bar')
    .data(positioned)
    .join('rect')
    .attr('class', 'drag-ghost-bar')
    .attr('x', d => x(d.entry.start))
    .attr('y', d => d.lane.top + BAR_INSET / 2)
    .attr('width', d => Math.max(2, (d.entry.end ? x(d.entry.end) : innerWidth) - x(d.entry.start)))
    .attr('height', d => d.lane.height - BAR_INSET)
    .attr('rx', 3)
    .attr('fill', colors.todayLine)
    .attr('fill-opacity', 0.25)
    .attr('stroke', colors.todayLine)
    .attr('stroke-width', 1.5);

  if (state.snapTarget !== null) {
    layer.append('line')
      .attr('class', 'drag-snap-guide')
      .attr('x1', x(state.snapTarget))
      .attr('x2', x(state.snapTarget))
      .attr('y1', 0)
      .attr('y2', innerHeight)
      .attr('stroke', colors.todayLine)
      .attr('stroke-width', 1.5)
      .attr('stroke-dasharray', '4,2');
  }

  renderLabel(ctx, positioned);
}

/**
 * Always two numbers: the date that will actually be written, and — greyed, and only when
 * a snap is in range — the boundary it is snapping to. Never one without the other, so
 * "it moved to the 1st" is never a surprise.
 */
function renderLabel(
  ctx: DragGhostContext,
  positioned: Array<{ entry: BarDragState['entries'][number]; lane: { top: number; height: number } }>,
): void {
  const { layer, x, colors, state } = ctx;

  const anchor = positioned.find(item => item.entry.rowId === state!.anchorRowId) ?? positioned[0];
  if (!anchor) return;

  const written = anchor.entry.end
    ? `${toDateString(anchor.entry.start)} → ${toDateString(anchor.entry.end)}`
    : `${toDateString(anchor.entry.start)} → …`;

  const group = layer.append('g')
    .attr('class', 'drag-ghost-label')
    .attr('pointer-events', 'none')
    .attr('transform', `translate(${x(anchor.entry.start) + 6}, ${anchor.lane.top - 4})`);

  const text = group.append('text')
    .attr('font-size', 10)
    .attr('fill', colors.tooltipText);

  text.append('tspan').text(written);

  if (state!.snapTarget !== null) {
    text.append('tspan')
      .attr('dx', 6)
      .attr('opacity', 0.65)
      .text(`⇥ ${toDateString(state!.snapTarget)}`);
  }

  if (state!.entries.length > 1) {
    text.append('tspan')
      .attr('dx', 6)
      .attr('opacity', 0.65)
      .text(`×${state!.entries.length}`);
  }

  // Measured after the tspans exist so the plate always fits the final string.
  const box = (text.node() as SVGTextElement).getBBox?.() ?? { x: 0, y: 0, width: 0, height: 0 };

  group.insert('rect', 'text')
    .attr('x', box.x - 4)
    .attr('y', box.y - 2)
    .attr('width', box.width + 8)
    .attr('height', box.height + 4)
    .attr('rx', 3)
    .attr('fill', colors.tooltipBg)
    .attr('stroke', colors.tooltipBorder);
}
