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

  // Sunday grid lines (only for institution rows) - visual week separators
  // At high zoom (dayWidthPx >= 14): show all Sundays
  // At lower zoom: show only the last Sunday of each month for orientation
  const days = d3.timeDay.range(minTime, maxTime)
  const institutionRows = layoutRows.filter(r => r.type === 'institution')
  const showAllSundays = typeof dayWidthPx === 'number' && dayWidthPx >= 14

  const dayBands: Array<{ day: Date; row: LayoutRow }> = []
  for (const day of days) {
    if (day.getDay() === 0) { // Sunday
      if (!showAllSundays) {
        // At lower zoom, only include the last Sunday of the month
        const nextSunday = d3.timeDay.offset(day, 7)
        if (nextSunday.getMonth() === day.getMonth()) {
          continue // Not the last Sunday of the month
        }
      }
      for (const row of institutionRows) {
        dayBands.push({ day, row })
      }
    }
  }

  g.append('g')
    .attr('class', 'sunday-grid')
    .selectAll('rect')
    .data(dayBands)
    .enter()
    .append('rect')
    .attr('x', d => x(d.day))
    .attr('y', d => rowTop(d.row.key))
    .attr('width', d => x(d3.timeDay.offset(d.day, 1)) - x(d.day))
    .attr('height', d => rowHeightFor(d.row.key))
    .attr('fill', colors.sundayMark)

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
