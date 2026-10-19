/**
 * renderBackground - Render row backgrounds and grid lines
 *
 * Renders zebra-striped row backgrounds, Sunday and month-start day marks,
 * year markers and row separator lines.
 */
import * as d3 from 'd3';

import type { GanttColors } from '../ganttColors';

import type { VacationPeriod } from '@/Composables/useVacationPeriods';

interface LayoutRow {
  key: string | number;
  /** 'group' (meetings chart) and 'tenant' (dutiable timeline) are header rows without grid lines. */
  type: string;
  height: number;
  top: number;
}

export interface BackgroundRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  /** Time scale */
  x: d3.ScaleTime<number, number>;
  /** Layout rows with positions */
  layoutRows: LayoutRow[];
  /** Inner width of SVG */
  innerWidth: number;
  /** Inner height of SVG */
  innerHeight: number;
  /** Color palette */
  colors: GanttColors;
  /** Start of the drawn range (the viewport, not the whole timeline) */
  minTime: Date;
  /** End of the drawn range */
  maxTime: Date;
  /** Get row top position by key */
  rowTop: (key: string | number) => number;
  /** Get row height by key */
  rowHeightFor: (key: string | number) => number;
  /** Pixels per day for zoom level detection */
  dayWidthPx?: number;
  /**
   * Absolute row index (in the full, uncalled row list) for zebra-stripe parity.
   * Required when `layoutRows` is a vertically-culled subset — without it, the
   * stripe parity would reset from the first visible row instead of staying
   * anchored to each row's true position, flipping on every scroll.
   */
  rowIndex?: (key: string | number) => number;
  /** Mark the 1st of each month; off for charts that draw their own month grid. */
  markMonthStarts?: boolean;
  /** Month starts inside these periods are skipped — the vacation shading marks them already. */
  vacationPeriods?: VacationPeriod[];
}

/**
 * Render row backgrounds with zebra striping and hover effects
 */
const isHeader = (row: LayoutRow) => row.type === 'group' || row.type === 'tenant';

export function renderBackground(ctx: BackgroundRenderContext): void {
  const { g, x, layoutRows, innerWidth, innerHeight, colors, minTime, maxTime, rowTop, rowHeightFor, dayWidthPx, rowIndex } = ctx;

  // Zebra row backgrounds with hover
  g.append('g')
    .attr('class', 'row-backgrounds')
    .selectAll('rect')
    .data(layoutRows.map((r, i) => ({ r, i: rowIndex ? rowIndex(r.key) : i })))
    .enter()
    .append('rect')
    .attr('x', 0)
    .attr('y', d => rowTop(d.r.key))
    .attr('width', innerWidth)
    .attr('height', d => d.r.height)
    .attr('fill', (d) => {
      if (isHeader(d.r)) {
        return colors.tenantRow;
      }
      return d.i % 2 === 0 ? colors.zebraEven : colors.zebraOdd;
    })
    .attr('class', 'transition-colors duration-150')
    .on('mouseenter', function (event, d: any) {
      if (!isHeader(d.r)) {
        d3.select(this).attr('fill', colors.rowHover);
      }
    })
    .on('mouseleave', function (event, d: any) {
      if (!isHeader(d.r)) {
        d3.select(this).attr('fill', d.i % 2 === 0 ? colors.zebraEven : colors.zebraOdd);
      }
    });

  // Day marks (institution rows only): every Sunday when zoomed in enough to read weeks,
  // and the 1st of each month, except Jan 1 (year marker) and days inside a vacation period.
  const days = d3.timeDay.range(minTime, maxTime);
  const institutionRows = layoutRows.filter(r => !isHeader(r));
  const showAllSundays = typeof dayWidthPx === 'number' && dayWidthPx >= 14;
  const vacationPeriods = ctx.vacationPeriods ?? [];
  const isMonthStartMark = (day: Date) => ctx.markMonthStarts === true
    && day.getDate() === 1
    && day.getMonth() !== 0
    && !vacationPeriods.some(period => period.start <= day && day <= period.end);

  const dayBands: Array<{ day: Date; row: LayoutRow; kind: 'sunday' | 'month-start' }> = [];
  for (const day of days) {
    const kind = isMonthStartMark(day) ? 'month-start' : (showAllSundays && day.getDay() === 0 ? 'sunday' : null);
    if (!kind) {
      continue;
    }
    for (const row of institutionRows) {
      dayBands.push({ day, row, kind });
    }
  }

  g.append('g')
    .attr('class', 'day-marks')
    .selectAll('rect')
    .data(dayBands)
    .enter()
    .append('rect')
    .attr('class', d => `${d.kind}-mark`)
    .attr('x', d => x(d.day))
    .attr('y', d => rowTop(d.row.key))
    .attr('width', d => x(d3.timeDay.offset(d.day, 1)) - x(d.day))
    .attr('height', d => rowHeightFor(d.row.key))
    .attr('fill', colors.sundayMark);

  // Year markers (vertical dashed lines at Jan 1)
  const yStart = d3.timeYear.floor(minTime);
  const yEnd = d3.timeYear.ceil(maxTime);
  const years = d3.timeYear.range(yStart, yEnd);

  g.append('g')
    .attr('class', 'year-markers')
    .selectAll('line')
    .data(years)
    .enter()
    .append('line')
    .attr('x1', d => x(d))
    .attr('x2', d => x(d))
    .attr('y1', 0)
    .attr('y2', innerHeight)
    .attr('stroke', colors.yearMarker)
    .attr('stroke-dasharray', '4,3')
    .attr('pointer-events', 'none');

  // Row separator lines
  g.append('g')
    .attr('class', 'row-separators')
    .selectAll('line')
    .data(layoutRows)
    .enter()
    .append('line')
    .attr('x1', 0)
    .attr('x2', innerWidth)
    .attr('y1', d => rowTop(d.key) + d.height)
    .attr('y2', d => rowTop(d.key) + d.height)
    .attr('stroke', colors.gridLine);
}
