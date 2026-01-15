/**
 * renderDragSelection - Render visual feedback for Shift+drag check-in creation
 * 
 * Draws a striped selection rectangle during drag operations on the Gantt chart.
 * The rectangle uses a distinct pattern to indicate check-in period selection.
 */
import * as d3 from 'd3'
import type { GanttColors } from '../ganttColors'
import type { DragSelectionState } from '../composables/useDragSelection'

export interface DragSelectionRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>
  /** Time scale */
  x: d3.ScaleTime<number, number>
  /** Current drag state */
  dragState: DragSelectionState
  /** Color palette */
  colors: GanttColors
  /** Whether dark mode is active */
  isDarkMode: boolean
}

/**
 * Setup the drag selection pattern in SVG defs
 */
export function setupDragSelectionPattern(
  defs: d3.Selection<SVGDefsElement, unknown, null, undefined>,
  isDarkMode: boolean
): void {
  // Remove existing pattern if present (for re-renders)
  defs.select('#dragSelectionPattern').remove()

  // Create diagonal stripe pattern for drag selection
  // Uses a blue/info color to distinguish from inactive periods (red)
  const pattern = defs.append('pattern')
    .attr('id', 'dragSelectionPattern')
    .attr('patternUnits', 'userSpaceOnUse')
    .attr('width', 8)
    .attr('height', 8)
    .attr('patternTransform', 'rotate(-45)')

  // Background
  pattern.append('rect')
    .attr('width', 8)
    .attr('height', 8)
    .attr('fill', isDarkMode ? 'rgba(59, 130, 246, 0.15)' : 'rgba(59, 130, 246, 0.1)')

  // Stripes
  pattern.append('line')
    .attr('x1', 0)
    .attr('y1', 0)
    .attr('x2', 0)
    .attr('y2', 8)
    .attr('stroke', isDarkMode ? 'rgba(59, 130, 246, 0.5)' : 'rgba(59, 130, 246, 0.4)')
    .attr('stroke-width', 4)
}

/**
 * Render the drag selection rectangle
 * 
 * This function should be called on each animation frame during drag
 * to update the selection rectangle position.
 */
export function renderDragSelection(ctx: DragSelectionRenderContext): void {
  const { g, x, dragState, colors, isDarkMode } = ctx

  // Remove existing selection rectangle
  g.selectAll('.drag-selection-rect').remove()

  // Only render if actively dragging with valid dates
  if (!dragState.isDragging || !dragState.startDate || !dragState.endDate) {
    return
  }

  // Calculate rectangle bounds
  const x1 = x(dragState.startDate)
  const x2 = x(dragState.endDate)
  const left = Math.min(x1, x2)
  const width = Math.abs(x2 - x1)

  // Don't render if width is too small
  if (width < 2) return

  const group = g.append('g')
    .attr('class', 'drag-selection-rect')
    .attr('pointer-events', 'none')

  // Main selection rectangle with pattern fill
  group.append('rect')
    .attr('x', left)
    .attr('y', dragState.y + 2) // Small padding from row top
    .attr('width', width)
    .attr('height', dragState.rowHeight - 4) // Small padding from row bottom
    .attr('fill', 'url(#dragSelectionPattern)')
    .attr('stroke', isDarkMode ? 'rgba(59, 130, 246, 0.7)' : 'rgba(59, 130, 246, 0.6)')
    .attr('stroke-width', 1.5)
    .attr('stroke-dasharray', '4,2')
    .attr('rx', 4)
    .attr('ry', 4)

  // Start marker line
  group.append('line')
    .attr('x1', x1)
    .attr('x2', x1)
    .attr('y1', dragState.y)
    .attr('y2', dragState.y + dragState.rowHeight)
    .attr('stroke', isDarkMode ? 'rgba(59, 130, 246, 0.9)' : 'rgba(59, 130, 246, 0.8)')
    .attr('stroke-width', 2)

  // End marker line
  group.append('line')
    .attr('x1', x2)
    .attr('x2', x2)
    .attr('y1', dragState.y)
    .attr('y2', dragState.y + dragState.rowHeight)
    .attr('stroke', isDarkMode ? 'rgba(59, 130, 246, 0.9)' : 'rgba(59, 130, 246, 0.8)')
    .attr('stroke-width', 2)

  // Date label (floating above selection)
  const startStr = formatDateShort(dragState.startDate)
  const endStr = formatDateShort(dragState.endDate)
  const labelText = dragState.startDate <= dragState.endDate 
    ? `${startStr} → ${endStr}`
    : `${endStr} → ${startStr}`

  group.append('text')
    .attr('x', left + width / 2)
    .attr('y', dragState.y - 4)
    .attr('text-anchor', 'middle')
    .attr('font-size', 10)
    .attr('font-weight', 500)
    .attr('fill', isDarkMode ? 'rgba(59, 130, 246, 1)' : 'rgba(37, 99, 235, 1)')
    .text(labelText)
}

/**
 * Format date as short string (MM-DD)
 */
function formatDateShort(date: Date): string {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${month}-${day}`
}

/**
 * Clear the drag selection rectangle
 */
export function clearDragSelection(
  g: d3.Selection<SVGGElement, unknown, null, undefined>
): void {
  g.selectAll('.drag-selection-rect').remove()
}
