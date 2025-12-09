/**
 * renderCenterLine - Render a fixed center line with date indicator
 * 
 * Creates a vertical dashed line fixed at the viewport center with a floating
 * date badge that updates as the user scrolls horizontally.
 */
import * as d3 from 'd3'
import type { GanttColors } from '../ganttColors'

export interface CenterLineRenderContext {
  /** Container element (for positioning the floating badge) */
  container: HTMLElement
  /** Scrollable container for the timeline */
  rightScroll: HTMLElement
  /** Time scale */
  x: d3.ScaleTime<number, number>
  /** Color palette */
  colors: GanttColors
  /** Left margin offset */
  marginLeft: number
  /** Axis height (for positioning below axis) */
  axisHeight: number
  /** Locale for date formatting */
  locale?: string
  /** Callback to navigate to today */
  onNavigateToToday?: () => void
}

export interface CenterLineManager {
  /** Update the center line position and date (call on scroll) */
  update: () => void
  /** Destroy the center line elements */
  destroy: () => void
}

/**
 * Create and render the center line with date indicator
 */
export function createCenterLine(ctx: CenterLineRenderContext): CenterLineManager {
  const { container, rightScroll, x, colors, marginLeft, axisHeight, locale = 'lt', onNavigateToToday } = ctx

  // Remove existing center line elements
  container.querySelectorAll('.gantt-center-line, .gantt-center-date').forEach(el => el.remove())

  // Create the vertical line element (positioned in the chart area)
  const lineEl = document.createElement('div')
  lineEl.className = 'gantt-center-line'
  lineEl.style.cssText = `
    position: absolute;
    top: ${axisHeight}px;
    bottom: 0;
    width: 1px;
    background: repeating-linear-gradient(
      to bottom,
      ${colors.centerLine} 0px,
      ${colors.centerLine} 4px,
      transparent 4px,
      transparent 8px
    );
    pointer-events: none;
    z-index: 40;
    opacity: 0.7;
  `
  container.appendChild(lineEl)

  // Create the date badge container (positioned at the top, fixed to viewport center)
  const dateBadge = document.createElement('div')
  dateBadge.className = 'gantt-center-date'
  dateBadge.style.cssText = `
    position: absolute;
    top: 2px;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 500;
    color: ${colors.centerDateText};
    background: ${colors.centerDateBg};
    border: 1px solid ${colors.centerDateBorder};
    border-radius: 4px;
    white-space: nowrap;
    z-index: 45;
    backdrop-filter: blur(4px);
  `
  container.appendChild(dateBadge)

  // Create the date text span
  const dateTextSpan = document.createElement('span')
  dateBadge.appendChild(dateTextSpan)

  // Create the reset button (hidden initially)
  const resetBtn = document.createElement('button')
  resetBtn.className = 'gantt-center-reset'
  resetBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 20 20" fill="currentColor">
    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
  </svg>`
  resetBtn.style.cssText = `
    display: none;
    align-items: center;
    justify-content: center;
    padding: 2px;
    margin: -2px -4px -2px 0;
    background: transparent;
    border: none;
    border-radius: 2px;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.15s;
    color: inherit;
  `
  resetBtn.title = locale === 'lt' ? 'Eiti į šiandien' : 'Go to today'
  resetBtn.addEventListener('mouseenter', () => { resetBtn.style.opacity = '1' })
  resetBtn.addEventListener('mouseleave', () => { resetBtn.style.opacity = '0.6' })
  resetBtn.addEventListener('click', (e) => {
    e.stopPropagation()
    onNavigateToToday?.()
  })
  dateBadge.appendChild(resetBtn)

  // Date formatter
  const fmtDate = new Intl.DateTimeFormat(locale, { 
    weekday: 'short',
    month: 'short', 
    day: 'numeric',
    year: 'numeric'
  })

  // Helper to check if date is today
  const isToday = (date: Date): boolean => {
    const today = new Date()
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear()
  }

  function update() {
    if (!rightScroll || !x) return
    
    // Calculate where the center line is visually pointing in SVG coordinates
    // scrollLeft = how far we've scrolled right
    // clientWidth = visible viewport width
    // So the center of the viewport in SVG coordinates is:
    const svgCenterX = rightScroll.scrollLeft + (rightScroll.clientWidth / 2)
    
    // The x scale is defined within the 'g' element which is translated by marginLeft
    // So to convert SVG coordinates to x-scale coordinates, subtract marginLeft
    const xScalePosition = svgCenterX - marginLeft
    
    // Clamp to valid range to avoid edge issues
    const xRange = x.range() as [number, number]
    const clampedPosition = Math.max(xRange[0], Math.min(xRange[1], xScalePosition))
    const centerDate = x.invert(clampedPosition)
    
    // Position the line and badge at the visual center of the rightScroll area
    // relative to the container (which includes left labels)
    const rightScrollRect = rightScroll.getBoundingClientRect()
    const containerRect = container.getBoundingClientRect()
    const lineLeft = (rightScrollRect.left - containerRect.left) + (rightScroll.clientWidth / 2)
    
    // Clamp line position to stay within the rightScroll area
    const minLeft = rightScrollRect.left - containerRect.left
    const maxLeft = minLeft + rightScroll.clientWidth
    const clampedLineLeft = Math.max(minLeft + 10, Math.min(maxLeft - 10, lineLeft))
    
    lineEl.style.left = `${clampedLineLeft}px`
    dateBadge.style.left = `${clampedLineLeft}px`
    
    // Update the date text
    dateTextSpan.textContent = fmtDate.format(centerDate)
    
    // Show/hide reset button based on whether we're at today
    if (onNavigateToToday && !isToday(centerDate)) {
      resetBtn.style.display = 'flex'
      dateBadge.style.pointerEvents = 'auto'
    } else {
      resetBtn.style.display = 'none'
      dateBadge.style.pointerEvents = 'none'
    }
  }

  // Initial update
  update()

  return {
    update,
    destroy: () => {
      lineEl.remove()
      dateBadge.remove()
    }
  }
}
