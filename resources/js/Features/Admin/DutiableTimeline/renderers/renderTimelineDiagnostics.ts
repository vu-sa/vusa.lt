import * as d3 from 'd3';

import type { TimelineColors } from '../timelineColors';
import type { TimelineLayoutRow } from '../types';

export interface TimelineDiagnosticsContext {
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  timelineColors: TimelineColors;
  layoutRows: TimelineLayoutRow[];
  /** Row ids carrying at least one finding, by worst severity. */
  severityByRow: Map<string, 'error' | 'warning' | 'info'>;
}

/**
 * A 3px gutter tick on every lane with a finding.
 *
 * Deliberately at the chart's left edge rather than on the bar: a finding is about a row,
 * not about a date, and marking the bar would compete with the off-boundary notch that
 * already means something else there.
 */
export function renderTimelineDiagnostics(ctx: TimelineDiagnosticsContext): void {
  const { g, timelineColors, layoutRows, severityByRow } = ctx;

  const marked = layoutRows.flatMap((lane) => {
    const severity = lane.row ? severityByRow.get(lane.row.id) : undefined;

    return severity ? [{ lane, severity }] : [];
  });

  const layer = g.append('g').attr('class', 'timeline-diagnostics').attr('pointer-events', 'none');

  layer.selectAll('rect.diagnostic-tick')
    .data(marked)
    .join('rect')
    .attr('class', 'diagnostic-tick')
    .attr('x', 0)
    .attr('y', d => d.lane.top + 2)
    .attr('width', 3)
    .attr('height', d => Math.max(2, d.lane.height - 4))
    // Red / amber / grey, rather than the meetings palette's blue and two near-invisible
    // greys — a severity tick that nobody can tell apart is not a severity tick.
    .attr('fill', d => timelineColors.severity[d.severity]);
}
