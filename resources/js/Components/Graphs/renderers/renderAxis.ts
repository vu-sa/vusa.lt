/**
 * renderAxis - Render the time axis with ticks, month headers, and year labels
 * 
 * Renders the top axis showing week/month ticks and year labels.
 * Adapts tick density based on zoom level.
 * Month headers are shown prominently and aligned with the month-end grid lines.
 */
import * as d3 from 'd3'
import type { GanttColors } from '../ganttColors'

// Lithuanian month names
const LT_MONTHS_SHORT = ['Sau', 'Vas', 'Kov', 'Bal', 'Geg', 'Bir', 'Lie', 'Rgp', 'Rgs', 'Spa', 'Lap', 'Grd']
const LT_MONTHS_LONG = ['Sausis', 'Vasaris', 'Kovas', 'Balandis', 'Gegužė', 'Birželis', 'Liepa', 'Rugpjūtis', 'Rugsėjis', 'Spalis', 'Lapkritis', 'Gruodis']

export interface AxisRenderContext {
  /** Axis SVG element */
  axisSvg: d3.Selection<SVGSVGElement, unknown, null, undefined>
  /** Time scale */
  x: d3.ScaleTime<number, number>
  /** Left margin */
  marginLeft: number
  /** Axis height */
  axisHeight: number
  /** Current day width in pixels */
  dayWidthPx: number
  /** Minimum time */
  minTime: Date
  /** Maximum time */
  maxTime: Date
  /** Color palette */
  colors: GanttColors
  /** Locale for month names (default: 'en') */
  locale?: string
}

/**
 * Get month formatter based on locale
 */
function getMonthFormatter(locale: string, short: boolean): (date: Date) => string {
  if (locale === 'lt') {
    return (date: Date) => short ? LT_MONTHS_SHORT[date.getMonth()]! : LT_MONTHS_LONG[date.getMonth()]!
  }
  // Default to d3 format for English and other locales
  return short ? d3.timeFormat('%b') : d3.timeFormat('%B')
}

/**
 * Render the time axis
 */
export function renderAxis(ctx: AxisRenderContext): void {
  const { axisSvg, x, marginLeft, axisHeight, dayWidthPx, minTime, maxTime, colors, locale = 'en' } = ctx

  if (axisSvg.empty()) return

  const axisG = axisSvg.append('g')
    .attr('transform', `translate(${marginLeft},${axisHeight - 2})`)

  // Calculate tick interval based on zoom level - show months more frequently
  let tickInterval: ReturnType<typeof d3.timeWeek.every>
  let showMonthLabels = true
  
  if (dayWidthPx < 3) {
    tickInterval = d3.timeMonth.every(2)
    showMonthLabels = true
  } else if (dayWidthPx < 4) {
    tickInterval = d3.timeMonth.every(1)
    showMonthLabels = true
  } else if (dayWidthPx < 6) {
    tickInterval = d3.timeWeek.every(2)
    showMonthLabels = true
  } else if (dayWidthPx < 10) {
    tickInterval = d3.timeWeek.every(1)
    showMonthLabels = true
  } else {
    tickInterval = d3.timeWeek.every(1)
    showMonthLabels = dayWidthPx >= 12 // Show months when zoomed in enough
  }

  // Get localized month formatter for tick labels
  const shortMonthFmt = getMonthFormatter(locale, true)

  // Render week ticks (or month ticks at very low zoom)
  const xAxis = d3.axisTop<Date>(x)
    .ticks(tickInterval)
    .tickFormat(d => {
      // At lower zoom levels, only show the day number
      if (dayWidthPx < 10) {
        return d3.timeFormat('%d')(d)
      }
      // Use localized month name
      return `${shortMonthFmt(d)} ${d3.timeFormat('%d')(d)}`
    })

  const axisTicksG = axisG.append('g')
    .attr('class', 'text-[10px]')
    .call(xAxis as any)

  axisTicksG.selectAll('line,path')
    .attr('stroke', colors.gridLine)

  // Style tick labels
  axisTicksG.selectAll<SVGTextElement, Date>('text')
    .attr('fill', colors.axisText)
    .style('font-size', dayWidthPx < 10 ? '9px' : '10px')
    .style('visibility', d => {
      // Hide first week of month labels if we're showing month headers
      if (d instanceof Date && showMonthLabels && d.getDate() <= 7) {
        return 'hidden'
      }
      // Hide January labels if showing year labels
      if (d instanceof Date && d.getMonth() === 0 && d.getDate() <= 7) {
        return 'hidden'
      }
      return 'visible'
    })

  // Month headers - shown at the start of each month
  if (showMonthLabels) {
    const mStart = d3.timeMonth.floor(minTime)
    const mEnd = d3.timeMonth.ceil(maxTime)
    const months = d3.timeMonth.range(mStart, d3.timeMonth.offset(mEnd, 1))
    
    // Choose format based on zoom level - use localized names
    const monthFmt = getMonthFormatter(locale, dayWidthPx < 8)

    const monthLabelsG = axisG.append('g')
      .attr('class', 'month-labels')

    months.forEach(monthStart => {
      const monthEnd = d3.timeMonth.offset(monthStart, 1)
      const startX = x(monthStart)
      const endX = x(monthEnd)
      const monthWidth = endX - startX
      
      // Skip months that are too narrow to display
      if (monthWidth < 30) return
      
      // Skip January (year label is shown instead)
      if (monthStart.getMonth() === 0) return

      // Calculate text width to position at start of month
      const labelX = startX + 4

      monthLabelsG.append('text')
        .attr('x', labelX)
        .attr('y', -8)
        .attr('fill', colors.monthHeaderText)
        .style('font-size', '11px')
        .style('font-weight', '500')
        .text(monthFmt(monthStart))
    })
  }

  // Year labels - shown at Jan 1
  const yStart = d3.timeYear.floor(minTime)
  const yEnd = d3.timeYear.ceil(maxTime)
  const years = d3.timeYear.range(yStart, yEnd)
  const yearFmt = d3.timeFormat('%Y')

  axisG.append('g')
    .attr('class', 'year-labels')
    .selectAll('text')
    .data(years)
    .enter()
    .append('text')
    .attr('x', d => x(d) + 4)
    .attr('y', -8)
    .attr('fill', colors.axisText)
    .style('font-size', '11px')
    .style('font-weight', '600')
    .text(d => yearFmt(d))
}
