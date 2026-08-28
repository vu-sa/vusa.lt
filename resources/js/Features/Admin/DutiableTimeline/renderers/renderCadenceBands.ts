import * as d3 from 'd3';

import type { GanttColors } from '@/Components/Graphs/ganttColors';

import type { TimelineColors } from '../timelineColors';
import type { ParsedCadence } from '../types';

export interface CadenceBandContext {
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  x: d3.ScaleTime<number, number>;
  innerHeight: number;
  colors: GanttColors;
  timelineColors: TimelineColors;
  cadences: ParsedCadence[];
  /** Terms the cadence filter selected. Empty means "no filter", not "nothing selected". */
  highlightedIds?: Set<string>;
  headerHeight?: number;
}

/**
 * Term bands behind the bars. This is the reference an admin reads drift against —
 * a bar that does not line up with a band edge is the whole problem made visible.
 *
 * Consecutive bands alternate between two tints, the way table rows do: one flat green
 * across the whole chart read as a single wash, which is exactly the wrong reading.
 */
export function renderCadenceBands(ctx: CadenceBandContext): void {
  const { g, x, innerHeight, colors, timelineColors, cadences } = ctx;
  const highlighted = ctx.highlightedIds ?? new Set<string>();
  const filtering = highlighted.size > 0;

  // Parity follows chronological order, not payload order, or a global and an override
  // band starting in the same year would land on the same tint side by side.
  const parity = new Map(
    [...cadences]
      .sort((a, b) => a.startDate.getTime() - b.startDate.getTime())
      .map((cadence, index) => [cadence.id, index % 2]),
  );

  const band = g.append('g').attr('class', 'cadence-bands').attr('pointer-events', 'none');

  band.selectAll('rect.cadence-band')
    .data(cadences)
    .join('rect')
    .attr('class', 'cadence-band')
    .attr('x', d => x(d.startDate))
    .attr('y', 0)
    .attr('width', d => Math.max(0, x(d.endDate) - x(d.startDate)))
    .attr('height', innerHeight)
    // Under a filter the alternating tints stop carrying meaning: what matters is which
    // term the chart is about, so the selection takes the ink and the rest gives it up.
    .attr('fill', (d) => {
      if (!filtering) return timelineColors.cadenceBand[parity.get(d.id) ?? 0];

      return highlighted.has(d.id) ? timelineColors.cadenceBandHighlight : timelineColors.cadenceBandDim;
    })
    .attr('stroke', d => (filtering && highlighted.has(d.id)
      ? timelineColors.cadenceBandHighlightStroke
      : timelineColors.cadenceBandStroke))
    .attr('stroke-width', d => (filtering && highlighted.has(d.id) ? 2 : 1));

  // Dashed guides on both edges: these are the snap targets, so they must be
  // visible before the drag rather than only during it.
  const edges = cadences.flatMap(c => [
    { date: c.startDate, cadence: c, edge: 'start' as const },
    { date: c.endDate, cadence: c, edge: 'end' as const },
  ]);

  band.selectAll('line.cadence-edge')
    .data(edges)
    .join('line')
    .attr('class', 'cadence-edge')
    .attr('x1', d => x(d.date))
    .attr('x2', d => x(d.date))
    .attr('y1', 0)
    .attr('y2', innerHeight)
    .attr('stroke', d => (filtering && highlighted.has(d.cadence.id)
      ? timelineColors.cadenceBandHighlightStroke
      : timelineColors.cadenceBandStroke))
    .attr('stroke-width', 1)
    .attr('stroke-dasharray', d => (filtering && highlighted.has(d.cadence.id) ? null : '3,3'));
}

export interface CadenceHeaderContext {
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  x: d3.ScaleTime<number, number>;
  colors: GanttColors;
  cadences: ParsedCadence[];
  highlightedIds?: Set<string>;
  y: number;
}

/** Term name chips, drawn into the sticky header so they survive vertical scroll. */
export function renderCadenceLabels(ctx: CadenceHeaderContext): void {
  const { g, x, colors, cadences, y } = ctx;
  const highlighted = ctx.highlightedIds ?? new Set<string>();
  const filtering = highlighted.size > 0;

  const labels = g.append('g').attr('class', 'cadence-labels').attr('pointer-events', 'none');

  labels.selectAll('text.cadence-label')
    .data(cadences.filter(c => x(c.endDate) - x(c.startDate) > 40))
    .join('text')
    .attr('class', 'cadence-label')
    .attr('x', d => (x(d.startDate) + x(d.endDate)) / 2)
    .attr('y', y)
    .attr('text-anchor', 'middle')
    .attr('fill', colors.axisText)
    .attr('font-size', 9)
    .attr('font-weight', d => (filtering && highlighted.has(d.id) ? 700 : 400))
    .attr('opacity', (d) => {
      if (!filtering) return 0.8;

      return highlighted.has(d.id) ? 1 : 0.35;
    })
    .text(d => d.label);
}
