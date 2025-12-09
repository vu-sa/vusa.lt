/**
 * renderGaps - Render check-in/gap lines on the Gantt chart
 * 
 * Renders horizontal lines representing check-in periods (gaps between meetings).
 */
import * as d3 from 'd3'
import type { GanttColors } from '../ganttColors'

interface ParsedGap {
  institution_id: string | number
  fromDate: Date
  untilDate: Date
  mode?: 'heads_up' | 'no_meetings'
  note?: string
}

export interface GapRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>
  /** Time scale */
  x: d3.ScaleTime<number, number>
  /** Gaps data */
  gaps: ParsedGap[]
  /** Color palette */
  colors: GanttColors
  /** Get row center Y position */
  rowCenter: (key: string | number) => number
  /** Callback when gap is clicked (to create meeting) */
  onCreateMeeting?: (payload: { institution_id: string | number; suggestedAt: Date }) => void
}

/**
 * Render gap/check-in lines
 */
export function renderGaps(ctx: GapRenderContext): void {
  const { g, x, gaps, colors, rowCenter, onCreateMeeting } = ctx

  const gapGroup = g.append('g').attr('class', 'gap-lines')

  gapGroup
    .selectAll('line')
    .data(gaps)
    .enter()
    .append('line')
    .attr('x1', d => x(d.fromDate))
    .attr('x2', d => x(d.untilDate))
    .attr('y1', d => rowCenter(d.institution_id))
    .attr('y2', d => rowCenter(d.institution_id))
    .attr('stroke-width', 3)
    .attr('stroke-linecap', 'round')
    .attr('stroke', colors.gap)
    .style('cursor', onCreateMeeting ? 'pointer' : 'default')
    .on('click', (event, d: any) => {
      if (onCreateMeeting) {
        onCreateMeeting({ institution_id: d.institution_id, suggestedAt: d.fromDate })
      }
    })
    .append('title')
    .text(d => {
      const dateRange = `${d.fromDate.toLocaleDateString()} → ${d.untilDate.toLocaleDateString()}`
      return d.note ? `${d.note}\n${dateRange}` : `Check-in: ${dateRange}`
    })
}
