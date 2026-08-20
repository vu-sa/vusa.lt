import * as d3 from 'd3';

import type { GanttColors } from '@/Components/Graphs/ganttColors';
import { formatMonthShort } from '@/Utils/IntlTime';

import { MONTH_LABEL_MIN_WIDTH } from '../constants';
import type { TimelineColors } from '../timelineColors';

export interface MonthGridContext {
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  x: d3.ScaleTime<number, number>;
  innerHeight: number;
  colors: GanttColors;
  timelineColors: TimelineColors;
  monthWidthPx: number;
}

/**
 * Month columns with hard boundaries, plus a year band.
 *
 * Deliberately not `renderAxis` from the meetings chart: that draws a day-scale axis
 * with tick marks, whereas editing terms needs visible *column edges* to drag against.
 */
export function renderMonthGrid(ctx: MonthGridContext): void {
  const { g, x, innerHeight, colors, timelineColors } = ctx;

  const [start, end] = x.domain();
  const months = d3.timeMonth.range(d3.timeMonth.floor(start), d3.timeMonth.offset(end, 1));

  const grid = g.append('g').attr('class', 'month-grid');

  // Alternating bands make a column countable without reading its label — which is
  // what "drag two columns right" relies on. The meetings palette's `zebraOdd` is
  // transparent in both themes, so the timeline carries its own tint.
  grid.selectAll('rect.month-band')
    .data(months.filter((_, i) => i % 2 === 1))
    .join('rect')
    .attr('class', 'month-band')
    .attr('x', d => x(d))
    .attr('y', 0)
    .attr('width', d => Math.max(0, x(d3.timeMonth.offset(d, 1)) - x(d)))
    .attr('height', innerHeight)
    .attr('fill', timelineColors.monthBand)
    .attr('pointer-events', 'none');

  grid.selectAll('line.month-boundary')
    .data(months)
    .join('line')
    .attr('class', 'month-boundary')
    .attr('x1', d => x(d))
    .attr('x2', d => x(d))
    .attr('y1', 0)
    .attr('y2', innerHeight)
    .attr('stroke', colors.gridLine)
    .attr('stroke-width', 1)
    .attr('pointer-events', 'none');

  // January gets a stronger rule so years are scannable at any zoom.
  grid.selectAll('line.year-boundary')
    .data(months.filter(d => d.getMonth() === 0))
    .join('line')
    .attr('class', 'year-boundary')
    .attr('x1', d => x(d))
    .attr('x2', d => x(d))
    .attr('y1', 0)
    .attr('y2', innerHeight)
    .attr('stroke', colors.yearMarker)
    .attr('stroke-width', 1.5)
    .attr('pointer-events', 'none');
}

export interface MonthHeaderContext {
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  x: d3.ScaleTime<number, number>;
  colors: GanttColors;
  monthWidthPx: number;
  headerHeight: number;
}

/**
 * The sticky three-tier header: years on top, cadence names in the middle (drawn by
 * renderCadenceLabels), month abbreviations at the bottom.
 *
 * The tiers are separate because a centred cadence chip and a left-anchored year label
 * sat on the same line and overlapped wherever a term began near a January.
 */
export function renderMonthHeader(ctx: MonthHeaderContext): void {
  const { g, x, colors, monthWidthPx, headerHeight } = ctx;

  const [start, end] = x.domain();
  const months = d3.timeMonth.range(d3.timeMonth.floor(start), d3.timeMonth.offset(end, 1));
  const years = d3.timeYear.range(d3.timeYear.floor(start), d3.timeYear.offset(end, 1));

  const header = g.append('g').attr('class', 'month-header');

  header.append('rect')
    .attr('x', 0)
    .attr('y', 0)
    .attr('width', Math.max(0, x(end) - x(start)))
    .attr('height', headerHeight)
    .attr('fill', colors.axisBg);

  header.selectAll('text.year-label')
    .data(years)
    .join('text')
    .attr('class', 'year-label')
    .attr('x', d => x(d) + 6)
    .attr('y', 12)
    .attr('fill', colors.axisText)
    .attr('font-size', 11)
    .attr('font-weight', 600)
    .text(d => d.getFullYear());

  // Zoomed out, a three-letter name is still the readable unit — so rather than
  // degrading it to an initial, label every Nth column and let the rest breathe.
  const stride = Math.max(1, Math.ceil(MONTH_LABEL_MIN_WIDTH / monthWidthPx));

  header.selectAll('text.month-label')
    .data(months.filter((_, index) => index % stride === 0))
    .join('text')
    .attr('class', 'month-label')
    .attr('x', d => x(d) + monthWidthPx / 2)
    .attr('y', headerHeight - 5)
    .attr('text-anchor', 'middle')
    .attr('fill', colors.monthHeaderText)
    .attr('font-size', 9)
    .text(d => formatMonthShort(d));
}
