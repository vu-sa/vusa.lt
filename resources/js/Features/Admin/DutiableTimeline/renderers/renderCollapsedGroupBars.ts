import * as d3 from 'd3';

import type { GroupSummary } from '../composables/useDutiableLayout';
import type { TimelineColors } from '../timelineColors';
import type { TimelineLayoutRow } from '../types';

export interface CollapsedGroupBarContext {
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  x: d3.ScaleTime<number, number>;
  timelineColors: TimelineColors;
  layoutRows: TimelineLayoutRow[];
  collapsed: Set<string>;
  summaries: Map<string, GroupSummary>;
  innerWidth: number;
}

/** Thinner than a real bar on purpose: a summary must never be mistaken for a period. */
const BAR_HEIGHT = 6;

/**
 * One merged bar per collapsed group, spanning its earliest start to its latest end.
 *
 * Collapsing used to leave a bare name over empty space, so the only way to find out when
 * a duty was actually staffed was to expand it again. This says "somewhere in here" without
 * claiming the seat was continuously held — hence the flat wash rather than a bar shape.
 */
export function renderCollapsedGroupBars(ctx: CollapsedGroupBarContext): void {
  const { g, x, timelineColors, layoutRows, collapsed, summaries, innerWidth } = ctx;

  const lanes = layoutRows
    .filter(lane => lane.type === 'tenant' && collapsed.has(lane.key) && summaries.has(lane.key))
    .map(lane => ({ lane, summary: summaries.get(lane.key)! }));

  const layer = g.append('g').attr('class', 'collapsed-group-bars').attr('pointer-events', 'none');

  layer.selectAll('rect.collapsed-group-bar')
    .data(lanes)
    .join('rect')
    .attr('class', 'collapsed-group-bar')
    .attr('x', d => x(d.summary.start))
    .attr('y', d => d.lane.top + (d.lane.height - BAR_HEIGHT) / 2)
    .attr('width', (d) => {
      const right = d.summary.end ? x(d.summary.end) : innerWidth;

      return Math.max(2, right - x(d.summary.start));
    })
    .attr('height', BAR_HEIGHT)
    .attr('rx', 2)
    .attr('fill', timelineColors.former)
    .attr('opacity', 0.8);
}
