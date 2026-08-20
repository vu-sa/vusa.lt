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
    .attr('fill', d => timelineColors.cadenceBand[parity.get(d.id) ?? 0])
    .attr('stroke', timelineColors.cadenceBandStroke)
    .attr('stroke-width', 1);

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
    .attr('stroke', timelineColors.cadenceBandStroke)
    .attr('stroke-width', 1)
    .attr('stroke-dasharray', '3,3');
}

export interface CadenceHeaderContext {
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  x: d3.ScaleTime<number, number>;
  colors: GanttColors;
  cadences: ParsedCadence[];
  y: number;
}

/** Term name chips, drawn into the sticky header so they survive vertical scroll. */
export function renderCadenceLabels(ctx: CadenceHeaderContext): void {
  const { g, x, colors, cadences, y } = ctx;

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
    .attr('opacity', 0.8)
    .text(d => d.label);
}
