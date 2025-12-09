/**
 * renderBackground - Render row backgrounds and grid lines
 * 
 * Renders zebra-striped row backgrounds, Monday grid lines,
 * and row separator lines.
 */
import * as d3 from 'd3'
import type { GanttColors } from '../ganttColors'

interface LayoutRow {
  key: string | number
  type: 'tenant' | 'institution'
  height: number
  top: number
}

export interface BackgroundRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>
  /** Time scale */
  x: d3.ScaleTime<number, number>
  /** Layout rows with positions */
  layoutRows: LayoutRow[]
  /** Inner width of SVG */
  innerWidth: number
  /** Inner height of SVG */
  innerHeight: number
  /** Color palette */
  colors: GanttColors
  /** Minimum time */
  minTime: Date
  /** Maximum time */
  maxTime: Date
  /** Get row top position by key */
  rowTop: (key: string | number) => number
  /** Get row height by key */
  rowHeightFor: (key: string | number) => number
  /** Pixels per day for zoom level detection */
  dayWidthPx?: number
}

/**
 * Render row backgrounds with zebra striping and hover effects
 */
export function renderBackground(ctx: BackgroundRenderContext): void {
  const { g, x, layoutRows, innerWidth, innerHeight, colors, minTime, maxTime, rowTop, rowHeightFor, dayWidthPx } = ctx

  // Zebra row backgrounds with hover
  g.append('g')
    .attr('class', 'row-backgrounds')
    .selectAll('rect')
    .data(layoutRows.map((r, i) => ({ r, i })))
    .enter()
    .append('rect')
    .attr('x', 0)
    .attr('y', d => rowTop(d.r.key))
    .attr('width', innerWidth)
    .attr('height', d => d.r.height)
    .attr('fill', d => {
      if (d.r.type === 'tenant') {
        return colors.tenantRow
      }
      return d.i % 2 === 0 ? colors.zebraEven : colors.zebraOdd
    })
    .attr('class', 'transition-colors duration-150')
    .on('mouseenter', function (event, d: any) {
      if (d.r.type !== 'tenant') {
        d3.select(this).attr('fill', colors.rowHover)
      }
    })
    .on('mouseleave', function (event, d: any) {
      if (d.r.type !== 'tenant') {
        d3.select(this).attr('fill', d.i % 2 === 0 ? colors.zebraEven : colors.zebraOdd)
      }
    })

  // Monday grid lines (only for institution rows)
  // At very low zoom (dayWidthPx < 6), only show for last week of each month
  const days = d3.timeDay.range(minTime, maxTime)
  const institutionRows = layoutRows.filter(r => r.type === 'institution')
  const isVeryLowZoom = typeof dayWidthPx === 'number' && dayWidthPx < 6

  const dayBands: Array<{ day: Date; row: LayoutRow }> = []
  for (const day of days) {
    if (day.getDay() === 1) { // Monday
      // At very low zoom, only include Mondays in the last 7 days of the month
      if (isVeryLowZoom) {
        const daysInMonth = new Date(day.getFullYear(), day.getMonth() + 1, 0).getDate()
        const dayOfMonth = day.getDate()
        // Only include if in last week of month (last 7 days)
        if (dayOfMonth < daysInMonth - 6) {
          continue
        }
      }
      for (const row of institutionRows) {
        dayBands.push({ day, row })
      }
    }
  }

  g.append('g')
    .attr('class', 'monday-grid')
    .selectAll('rect')
    .data(dayBands)
    .enter()
    .append('rect')
    .attr('x', d => x(d.day))
    .attr('y', d => rowTop(d.row.key))
    .attr('width', d => x(d3.timeDay.offset(d.day, 1)) - x(d.day))
    .attr('height', d => rowHeightFor(d.row.key))
    .attr('fill', colors.gridLine)

  // Year markers (vertical dashed lines at Jan 1)
  const yStart = d3.timeYear.floor(minTime)
  const yEnd = d3.timeYear.ceil(maxTime)
  const years = d3.timeYear.range(yStart, yEnd)

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
    .attr('pointer-events', 'none')

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
    .attr('stroke', colors.gridLine)
}
