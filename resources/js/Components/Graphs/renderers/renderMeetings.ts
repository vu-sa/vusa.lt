/**
 * renderMeetings - Render meeting icons on the Gantt chart
 * 
 * Renders meeting dots/icons with proper styling based on completion status.
 * Includes click handling for navigation. Uses unified tooltip system.
 */
import * as d3 from 'd3'
import { router } from '@inertiajs/vue3'
import type { GanttColors } from '../ganttColors'
import type { GanttTooltipManager } from './GanttTooltip'
import { buildMeetingTooltipContent } from './GanttTooltip'

interface ParsedMeeting {
  id: string | number
  date: Date
  institution_id: string | number
  title?: string
  institution?: string
  completion_status?: 'complete' | 'incomplete' | 'no_items'
}

export interface MeetingRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>
  /** Container element for positioning */
  container: HTMLElement
  /** Time scale */
  x: d3.ScaleTime<number, number>
  /** Meetings data */
  meetings: ParsedMeeting[]
  /** Color palette */
  colors: GanttColors
  /** Get row center Y position */
  rowCenter: (key: string | number) => number
  /** Get row top position */
  rowTop: (key: string | number) => number
  /** Get row height */
  rowHeightFor: (key: string | number) => number
  /** Get institution name by ID */
  labelFor: (id: string | number) => string
  /** Whether interactive (clickable) */
  interactive: boolean
  /** Unified tooltip manager */
  tooltipManager?: GanttTooltipManager
  /** Callback when create-meeting is triggered */
  onCreateMeeting?: (payload: { institution_id: string | number; suggestedAt: Date }) => void
}

// Fluent UI people-team-20-filled SVG path (viewBox 0 0 20 20)
const MEETING_ICON_FILLED = 'M12.5 4.5a2.5 2.5 0 1 1-5 0a2.5 2.5 0 0 1 5 0m5 .5a2 2 0 1 1-4 0a2 2 0 0 1 4 0m-13 2a2 2 0 1 0 0-4a2 2 0 0 0 0 4M6 9.25C6 8.56 6.56 8 7.25 8h5.5c.69 0 1.25.56 1.25 1.25V14a4 4 0 0 1-8 0zm-1 0c0-.463.14-.892.379-1.25H3.25C2.56 8 2 8.56 2 9.25V13a3 3 0 0 0 3.404 2.973A5 5 0 0 1 5 14zM15 14c0 .7-.144 1.368-.404 1.973Q14.794 16 15 16a3 3 0 0 0 3-3V9.25C18 8.56 17.44 8 16.75 8h-2.129c.24.358.379.787.379 1.25z'

// Fluent UI people-team-20-regular SVG path for outline
const MEETING_ICON_REGULAR = 'M10 3a1.5 1.5 0 1 0 0 3a1.5 1.5 0 0 0 0-3M7.5 4.5a2.5 2.5 0 1 1 5 0a2.5 2.5 0 0 1-5 0m8-.5a1 1 0 1 0 0 2a1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0a2 2 0 0 1-4 0m-10 0a1 1 0 1 1 2 0a1 1 0 0 1-2 0m1-2a2 2 0 1 0 0 4a2 2 0 0 0 0-4m.6 11.998L5 15a2 2 0 0 1-2-2V9.25A.25.25 0 0 1 3.25 9h1.764c.04-.367.17-.708.365-1H3.25C2.56 8 2 8.56 2 9.25V13a3 3 0 0 0 3.404 2.973a5 5 0 0 1-.304-.975m9.496.975Q14.794 16 15 16a3 3 0 0 0 3-3V9.25C18 8.56 17.44 8 16.75 8h-2.129c.196.292.325.633.365 1h1.764a.25.25 0 0 1 .25.25V13a2 2 0 0 1-2.1 1.998a5 5 0 0 1-.304.975M7.25 8C6.56 8 6 8.56 6 9.25V14a4 4 0 0 0 8 0V9.25C14 8.56 13.44 8 12.75 8zM7 9.25A.25.25 0 0 1 7.25 9h5.5a.25.25 0 0 1 .25.25V14a3 3 0 1 1-6 0z'

const ICON_SIZE = 14

/**
 * Render meeting icons
 */
export function renderMeetings(ctx: MeetingRenderContext): void {
  const { g, container, x, meetings, colors, rowCenter, labelFor, interactive, tooltipManager } = ctx

  // Safety bands around meetings (±14 days green glow)
  const bandDays = 14
  g.append('g')
    .attr('class', 'safety-bands')
    .selectAll('rect')
    .data(meetings)
    .enter()
    .append('rect')
    .attr('x', d => x(d3.timeDay.offset(d.date, -bandDays)))
    .attr('y', d => ctx.rowTop(d.institution_id))
    .attr('width', d => Math.max(2, x(d3.timeDay.offset(d.date, bandDays)) - x(d3.timeDay.offset(d.date, -bandDays))))
    .attr('height', d => ctx.rowHeightFor(d.institution_id))
    .attr('fill', 'url(#safeBand)')
    .attr('stroke', 'transparent')

  // Meeting icons group
  const dotGroup = g.append('g').attr('class', 'meeting-icons')

  const dots = dotGroup
    .selectAll('g.meeting-icon')
    .data(meetings)
    .enter()
    .append('g')
    .attr('class', 'meeting-icon')
    .attr('transform', d => `translate(${x(d.date) - ICON_SIZE / 2}, ${rowCenter(d.institution_id) - ICON_SIZE / 2})`)
    .attr('tabindex', interactive ? 0 : -1)
    .style('cursor', interactive ? 'pointer' : 'default')
    .on('click', (event: MouseEvent, d: any) => {
      if (!interactive) return
      const routeFn = (window as any)?.route
      const url = routeFn ? routeFn('meetings.show', d.id) : `/admin/meetings/${d.id}`
      if (event.ctrlKey || event.metaKey || event.button === 1) {
        window.open(url, '_blank')
      } else {
        router.visit(url)
      }
    })
    .on('auxclick', (event: MouseEvent, d: any) => {
      if (!interactive || event.button !== 1) return
      event.preventDefault()
      const routeFn = (window as any)?.route
      const url = routeFn ? routeFn('meetings.show', d.id) : `/admin/meetings/${d.id}`
      window.open(url, '_blank')
    })
    .on('keydown', (event: KeyboardEvent, d: any) => {
      if (!interactive) return
      if (event.key === 'Enter' || event.key === ' ') {
        const routeFn = (window as any)?.route
        const url = routeFn ? routeFn('meetings.show', d.id) : `/admin/meetings/${d.id}`
        router.visit(url)
      }
    })

  // Add icon path
  dots.append('path')
    .attr('d', (d: any) => d.completion_status === 'no_items' ? MEETING_ICON_REGULAR : MEETING_ICON_FILLED)
    .attr('transform', `scale(${ICON_SIZE / 20})`)
    .attr('fill', (d: any) => {
      if (d.completion_status === 'complete') {
        return colors.meetingComplete
      } else if (d.completion_status === 'no_items') {
        return colors.meetingNoItems
      } else {
        return colors.meetingIncomplete
      }
    })

  // Add native tooltip as fallback (when no tooltipManager)
  if (!tooltipManager) {
    dots.append('title').text((d: any) => {
      let status = ''
      if (d.completion_status === 'complete') {
        status = ' ✓'
      } else if (d.completion_status === 'no_items') {
        status = ' (no agenda items)'
      } else {
        status = ' (incomplete)'
      }
      return (d.title || labelFor(d.institution_id) || new Date(d.date).toLocaleString()) + status
    })
  }

  const fmt = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' })

  // Use unified tooltip manager if provided
  if (tooltipManager) {
    dots
      .on('mouseenter', (event, d: any) => {
        const rect = container.getBoundingClientRect()
        const content = buildMeetingTooltipContent(d, labelFor, fmt)
        tooltipManager.show(content, event.clientX - rect.left, event.clientY - rect.top)
      })
      .on('mousemove', (event) => {
        const rect = container.getBoundingClientRect()
        tooltipManager.updatePosition(event.clientX - rect.left, event.clientY - rect.top)
      })
      .on('mouseleave', () => {
        tooltipManager.hide()
      })
  }
}
