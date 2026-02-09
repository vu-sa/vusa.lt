/**
 * renderTodayLine - Render the today line indicator
 *
 * Renders a vertical line at today's date position.
 */
import type * as d3 from 'd3';

import type { GanttColors } from '../ganttColors';

export interface TodayLineRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  /** Time scale */
  x: d3.ScaleTime<number, number>;
  /** Inner height of SVG */
  innerHeight: number;
  /** Minimum visible time */
  minTime: Date;
  /** Maximum visible time */
  maxTime: Date;
  /** Color palette */
  colors: GanttColors;
  /** Whether to show the today line */
  showTodayLine: boolean;
}

/**
 * Render today line indicator
 */
export function renderTodayLine(ctx: TodayLineRenderContext): void {
  const { g, x, innerHeight, minTime, maxTime, colors, showTodayLine } = ctx;

  if (!showTodayLine) return;

  const today = new Date();
  if (today < minTime || today > maxTime) return;

  g.append('line')
    .attr('class', 'today-line')
    .attr('x1', x(today))
    .attr('x2', x(today))
    .attr('y1', 0)
    .attr('y2', innerHeight)
    .attr('stroke', colors.todayLine)
    .attr('stroke-width', 1)
    .attr('pointer-events', 'none');
}
