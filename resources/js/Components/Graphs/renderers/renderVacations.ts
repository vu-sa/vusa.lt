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

  g.append('g')
    .attr('class', 'vacation-bands')
    .selectAll('rect')
    .data(vacationBands)
    .enter()
    .append('rect')
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
}
