/**
 * renderVacations - Render vacation period background bands
 * 
 * Renders semi-transparent colored bands for vacation periods
 * (summer, winter, easter) to provide visual context.
 */
import * as d3 from 'd3'
import type { GanttColors } from '../ganttColors'
import { getVacationPeriods, type VacationPeriod } from '@/Pages/Admin/Dashboard/Components/vacationConfig'

interface LayoutRow {
  key: string | number
  type: 'tenant' | 'institution'
  height: number
}

export interface VacationRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>
  /** Time scale */
  x: d3.ScaleTime<number, number>
  /** Layout rows */
  layoutRows: LayoutRow[]
  /** Inner width */
  innerWidth: number
  /** Minimum time */
  minTime: Date
  /** Maximum time */
  maxTime: Date
  /** Color palette */
  colors: GanttColors
  /** Get row top position */
  rowTop: (key: string | number) => number
  /** Get row height */
  rowHeightFor: (key: string | number) => number
}

/**
 * Render vacation period bands
 * 
 * Renders in layers:
 * 1. Solid background to cover weekend grid stripes
 * 2. Colored overlay for the vacation type
 * 3. Subtle left/right borders for definition
 */
export function renderVacations(ctx: VacationRenderContext): void {
  const { g, x, layoutRows, innerWidth, minTime, maxTime, colors, rowTop, rowHeightFor } = ctx

  const vacationPeriods = getVacationPeriods(minTime, maxTime)
  const institutionRows = layoutRows.filter(r => r.type === 'institution')

  // Create vacation band data for each period × institution row
  const vacationBands: Array<{ period: VacationPeriod; row: LayoutRow }> = []
  for (const period of vacationPeriods) {
    for (const row of institutionRows) {
      vacationBands.push({ period, row })
    }
  }

  const vacationGroup = g.append('g').attr('class', 'vacation-bands')

  // Helper to get stroke color for vacation type
  const getStrokeColor = (type: VacationPeriod['type']) => {
    switch (type) {
      case 'summer': return colors.vacationSummerStroke ?? colors.vacationSummer
      case 'winter': return colors.vacationWinterStroke ?? colors.vacationWinter
      case 'easter': return colors.vacationEasterStroke ?? colors.vacationEaster
      default: return colors.vacationDefaultStroke ?? colors.vacationDefault
    }
  }

  // Layer 1: Solid background rectangles to cover weekend stripes
  vacationGroup
    .selectAll('rect.vacation-solid-bg')
    .data(vacationBands)
    .enter()
    .append('rect')
    .attr('class', 'vacation-solid-bg')
    .attr('x', d => Math.max(0, x(d.period.start)))
    .attr('y', d => rowTop(d.row.key))
    .attr('width', d => {
      const startX = Math.max(0, x(d.period.start))
      const endX = Math.min(innerWidth, x(d.period.end))
      return Math.max(0, endX - startX)
    })
    .attr('height', d => rowHeightFor(d.row.key))
    .attr('fill', colors.vacationSolidBg ?? colors.axisBg)
    .attr('pointer-events', 'none')

  // Layer 2: Colored overlay rectangles
  vacationGroup
    .selectAll('rect.vacation-overlay')
    .data(vacationBands)
    .enter()
    .append('rect')
    .attr('class', 'vacation-overlay')
    .attr('x', d => Math.max(0, x(d.period.start)))
    .attr('y', d => rowTop(d.row.key))
    .attr('width', d => {
      const startX = Math.max(0, x(d.period.start))
      const endX = Math.min(innerWidth, x(d.period.end))
      return Math.max(0, endX - startX)
    })
    .attr('height', d => rowHeightFor(d.row.key))
    .attr('fill', d => {
      switch (d.period.type) {
        case 'summer': return colors.vacationSummer
        case 'winter': return colors.vacationWinter
        case 'easter': return colors.vacationEaster
        default: return colors.vacationDefault
      }
    })
    .attr('pointer-events', 'none')
    .append('title')
    .text(d => {
      const typeLabel = d.period.type === 'summer' ? 'Summer vacation' :
        d.period.type === 'winter' ? 'Winter vacation' :
          d.period.type === 'easter' ? 'Easter vacation' : 'Vacation'
      return `${typeLabel}: ${d.period.start.toLocaleDateString()} - ${d.period.end.toLocaleDateString()}`
    })

  // Layer 3: Left/right border lines for each vacation period (once per period, spanning all rows)
  // Group by period to avoid drawing duplicate borders for each row
  const uniquePeriods = vacationPeriods

  for (const period of uniquePeriods) {
    const startX = Math.max(0, x(period.start))
    const endX = Math.min(innerWidth, x(period.end))
    const strokeColor = getStrokeColor(period.type)

    // Calculate total height span for institution rows
    const firstRow = institutionRows[0]
    const lastRow = institutionRows[institutionRows.length - 1]
    if (!firstRow || !lastRow) continue

    const topY = rowTop(firstRow.key)
    const bottomY = rowTop(lastRow.key) + rowHeightFor(lastRow.key)

    // Left border
    if (startX > 0) {
      vacationGroup
        .append('line')
        .attr('class', 'vacation-border-left')
        .attr('x1', startX)
        .attr('x2', startX)
        .attr('y1', topY)
        .attr('y2', bottomY)
        .attr('stroke', strokeColor)
        .attr('stroke-width', 1)
        .attr('pointer-events', 'none')
    }

    // Right border
    if (endX < innerWidth) {
      vacationGroup
        .append('line')
        .attr('class', 'vacation-border-right')
        .attr('x1', endX)
        .attr('x2', endX)
        .attr('y1', topY)
        .attr('y2', bottomY)
        .attr('stroke', strokeColor)
        .attr('stroke-width', 1)
        .attr('pointer-events', 'none')
    }
  }
}
