/**
 * setupDefs - Create SVG gradient and pattern definitions
 * 
 * Sets up reusable SVG definitions like gradients for safety bands
 * and patterns for inactive periods.
 */
import * as d3 from 'd3'
import type { GanttColors } from '../ganttColors'

export interface DefsContext {
  /** D3 selection for the defs element */
  defs: d3.Selection<SVGDefsElement, unknown, null, undefined>
  /** Color palette */
  colors: GanttColors
  /** Whether dark mode is active */
  isDarkMode: boolean
}

/**
 * Create SVG definitions (gradients, patterns)
 */
export function setupDefs(ctx: DefsContext): void {
  const { defs, colors, isDarkMode } = ctx

  // Safety band gradient (green glow around meetings)
  const safeGrad = defs.append('linearGradient')
    .attr('id', 'safeBand')
    .attr('x1', '0%').attr('x2', '100%')
    .attr('y1', '0%').attr('y2', '0%')
  safeGrad.append('stop').attr('offset', '0%').attr('stop-color', colors.safetyBandStart)
  safeGrad.append('stop').attr('offset', '40%').attr('stop-color', colors.safetyBandMid)
  safeGrad.append('stop').attr('offset', '60%').attr('stop-color', colors.safetyBandMid)
  safeGrad.append('stop').attr('offset', '100%').attr('stop-color', colors.safetyBandEnd)

  // Diagonal stripe pattern for inactive periods
  const inactivePattern = defs.append('pattern')
    .attr('id', 'inactiveStripes')
    .attr('patternUnits', 'userSpaceOnUse')
    .attr('width', 8)
    .attr('height', 8)
    .attr('patternTransform', 'rotate(45)')
  inactivePattern.append('rect')
    .attr('width', 8)
    .attr('height', 8)
    .attr('fill', colors.zebraOdd)
  inactivePattern.append('line')
    .attr('x1', 0)
    .attr('y1', 0)
    .attr('x2', 0)
    .attr('y2', 8)
    .attr('stroke', isDarkMode ? 'rgba(239, 68, 68, 0.3)' : 'rgba(239, 68, 68, 0.2)')
    .attr('stroke-width', 4)
}
