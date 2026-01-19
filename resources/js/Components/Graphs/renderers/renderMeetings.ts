/**
 * renderMeetings - Render meeting icons on the Gantt chart
 * 
 * Renders meeting dots/icons with proper styling based on completion status.
 * Includes click handling for navigation. Uses unified tooltip system.
 */
import * as d3 from 'd3'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { trans as $t } from 'laravel-vue-i18n'
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
  // Whether the user has authorization for this meeting's institution
  authorized?: boolean
  // Meeting type slug for icon differentiation (in-person-meeting, remote-meeting, email-meeting)
  type_slug?: string
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
  /** Meeting periodicity per institution (days between expected meetings) */
  institutionPeriodicity?: Record<string | number, number>
}

// =============================================================================
// MEETING TYPE ICONS (Fluent UI 20px, viewBox 0 0 20 20)
// =============================================================================

// In-person meeting: people-team-20 (group of people)
const ICON_IN_PERSON_FILLED = 'M12.5 4.5a2.5 2.5 0 1 1-5 0a2.5 2.5 0 0 1 5 0m5 .5a2 2 0 1 1-4 0a2 2 0 0 1 4 0m-13 2a2 2 0 1 0 0-4a2 2 0 0 0 0 4M6 9.25C6 8.56 6.56 8 7.25 8h5.5c.69 0 1.25.56 1.25 1.25V14a4 4 0 0 1-8 0zm-1 0c0-.463.14-.892.379-1.25H3.25C2.56 8 2 8.56 2 9.25V13a3 3 0 0 0 3.404 2.973A5 5 0 0 1 5 14zM15 14c0 .7-.144 1.368-.404 1.973Q14.794 16 15 16a3 3 0 0 0 3-3V9.25C18 8.56 17.44 8 16.75 8h-2.129c.24.358.379.787.379 1.25z'
const ICON_IN_PERSON_REGULAR = 'M10 3a1.5 1.5 0 1 0 0 3a1.5 1.5 0 0 0 0-3M7.5 4.5a2.5 2.5 0 1 1 5 0a2.5 2.5 0 0 1-5 0m8-.5a1 1 0 1 0 0 2a1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0a2 2 0 0 1-4 0m-10 0a1 1 0 1 1 2 0a1 1 0 0 1-2 0m1-2a2 2 0 1 0 0 4a2 2 0 0 0 0-4m.6 11.998L5 15a2 2 0 0 1-2-2V9.25A.25.25 0 0 1 3.25 9h1.764c.04-.367.17-.708.365-1H3.25C2.56 8 2 8.56 2 9.25V13a3 3 0 0 0 3.404 2.973a5 5 0 0 1-.304-.975m9.496.975Q14.794 16 15 16a3 3 0 0 0 3-3V9.25C18 8.56 17.44 8 16.75 8h-2.129c.196.292.325.633.365 1h1.764a.25.25 0 0 1 .25.25V13a2 2 0 0 1-2.1 1.998a5 5 0 0 1-.304.975M7.25 8C6.56 8 6 8.56 6 9.25V14a4 4 0 0 0 8 0V9.25C14 8.56 13.44 8 12.75 8zM7 9.25A.25.25 0 0 1 7.25 9h5.5a.25.25 0 0 1 .25.25V14a3 3 0 1 1-6 0z'

// Remote meeting: device-meeting-room-remote-20 (screen with person)
const ICON_REMOTE_FILLED = 'M4 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h3v1H5.5a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1H13v-1h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm8 11H8v-1h4zM8 9a2 2 0 1 1 4 0a2 2 0 0 1-4 0'
const ICON_REMOTE_REGULAR = 'M2 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-3v1h1.5a.5.5 0 0 1 0 1h-9a.5.5 0 0 1 0-1H7v-1H4a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1zm4 10h4v-1H8zM8 9a2 2 0 1 1 4 0a2 2 0 0 1-4 0m2-1a1 1 0 1 0 0 2a1 1 0 0 0 0-2'

// Email meeting: mail-20 (envelope)
const ICON_EMAIL_FILLED = 'M2 5.5A1.5 1.5 0 0 1 3.5 4h13A1.5 1.5 0 0 1 18 5.5v.401l-8 4.48l-8-4.48zm0 1.544v7.456A1.5 1.5 0 0 0 3.5 16h13a1.5 1.5 0 0 0 1.5-1.5V7.044l-7.703 4.314a.5.5 0 0 1-.486.008z'
const ICON_EMAIL_REGULAR = 'M3.5 4A1.5 1.5 0 0 0 2 5.5v9A1.5 1.5 0 0 0 3.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 16.5 4zm-.232 1.357A.5.5 0 0 1 3.5 5h13a.5.5 0 0 1 .232.043L10 8.9zM17 6.088l-6.703 3.752a.5.5 0 0 1-.486.008L3 6.088V14.5a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5z'

// Other/fallback: calendar-ltr-20 (calendar)
const ICON_CALENDAR_FILLED = 'M7 11a1 1 0 1 0 0-2a1 1 0 0 0 0 2m1 2a1 1 0 1 1-2 0a1 1 0 0 1 2 0m2-2a1 1 0 1 0 0-2a1 1 0 0 0 0 2m4-1a1 1 0 1 1-2 0a1 1 0 0 1 2 0m-1 4a1 1 0 1 0 0-2a1 1 0 0 0 0 2m-2-3a1 1 0 1 1-2 0a1 1 0 0 1 2 0M17 5.5A2.5 2.5 0 0 0 14.5 3h-9A2.5 2.5 0 0 0 3 5.5v9A2.5 2.5 0 0 0 5.5 17h9a2.5 2.5 0 0 0 2.5-2.5zM4 7h12v7.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 4 14.5z'
const ICON_CALENDAR_REGULAR = 'M17 5.5A2.5 2.5 0 0 0 14.5 3h-9A2.5 2.5 0 0 0 3 5.5v9A2.5 2.5 0 0 0 5.5 17h9a2.5 2.5 0 0 0 2.5-2.5zM14.5 4a1.5 1.5 0 0 1 1.5 1.5V6H4v-.5A1.5 1.5 0 0 1 5.5 4zM4 14.5V7h12v7.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 4 14.5M7 11a1 1 0 1 0 0-2a1 1 0 0 0 0 2m1 2a1 1 0 1 1-2 0a1 1 0 0 1 2 0m2-2a1 1 0 1 0 0-2a1 1 0 0 0 0 2m4-1a1 1 0 1 1-2 0a1 1 0 0 1 2 0m-1 4a1 1 0 1 0 0-2a1 1 0 0 0 0 2m-2-3a1 1 0 1 1-2 0a1 1 0 0 1 2 0'

// Legacy aliases for backward compatibility
const MEETING_ICON_FILLED = ICON_IN_PERSON_FILLED
const MEETING_ICON_REGULAR = ICON_IN_PERSON_REGULAR

const ICON_SIZE = 14

/**
 * Get the appropriate icon path based on meeting type and completion status
 * @param typeSlug - The meeting type slug (in-person-meeting, remote-meeting, email-meeting)
 * @param completionStatus - The completion status for filled/regular variant selection
 * @param authorized - Whether the user is authorized (unauthorized always uses filled)
 * @returns SVG path string for the icon
 */
function getMeetingIconPath(
  typeSlug?: string,
  completionStatus?: 'complete' | 'incomplete' | 'no_items',
  authorized?: boolean
): string {
  // Determine if we should use filled or regular variant
  // Unauthorized meetings and meetings with items use filled, no_items uses regular
  const useFilled = authorized === false || completionStatus !== 'no_items'
  
  switch (typeSlug) {
    case 'in-person-meeting':
      return useFilled ? ICON_IN_PERSON_FILLED : ICON_IN_PERSON_REGULAR
    case 'remote-meeting':
      return useFilled ? ICON_REMOTE_FILLED : ICON_REMOTE_REGULAR
    case 'email-meeting':
      return useFilled ? ICON_EMAIL_FILLED : ICON_EMAIL_REGULAR
    default:
      // Fallback to calendar for unknown types
      return useFilled ? ICON_CALENDAR_FILLED : ICON_CALENDAR_REGULAR
  }
}

/**
 * Render meeting icons
 */
export function renderMeetings(ctx: MeetingRenderContext): void {
  const { g, container, x, meetings, colors, rowCenter, labelFor, interactive, tooltipManager, institutionPeriodicity } = ctx

  // Default periodicity in days (half for each side of the meeting)
  const defaultPeriodicity = 30

  // Calculate rectangle dimensions with padding (similar to check-ins)
  const getRectHeight = (institutionId: string | number) => {
    const rowH = ctx.rowHeightFor(institutionId)
    return Math.min(rowH - 6, 18) // Max 18px height, with 3px padding top/bottom
  }

  const getRectY = (institutionId: string | number) => {
    const rowH = ctx.rowHeightFor(institutionId)
    const rectH = getRectHeight(institutionId)
    return ctx.rowTop(institutionId) + (rowH - rectH) / 2
  }

  // Safety bands around meetings - use half of institution's periodicity for each side
  // This creates a "safe zone" where meetings are expected
  // Styled with border and padding similar to check-in periods
  g.append('g')
    .attr('class', 'safety-bands')
    .selectAll('rect')
    .data(meetings)
    .enter()
    .append('rect')
    .attr('x', d => {
      const periodicity = institutionPeriodicity?.[d.institution_id] ?? defaultPeriodicity
      const bandDays = Math.floor(periodicity / 2)
      return x(d3.timeDay.offset(d.date, -bandDays))
    })
    .attr('y', d => getRectY(d.institution_id))
    .attr('width', d => {
      const periodicity = institutionPeriodicity?.[d.institution_id] ?? defaultPeriodicity
      const bandDays = Math.floor(periodicity / 2)
      return Math.max(2, x(d3.timeDay.offset(d.date, bandDays)) - x(d3.timeDay.offset(d.date, -bandDays)))
    })
    .attr('height', d => getRectHeight(d.institution_id))
    .attr('fill', colors.safetyBandFlat ?? colors.safetyBandMid)
    .attr('stroke', colors.safetyBandStroke ?? 'transparent')
    .attr('stroke-width', 1)
    .attr('rx', 4)
    .attr('ry', 4)

  // Meeting icons group
  const dotGroup = g.append('g').attr('class', 'meeting-icons')

  // Hit area size (larger than icon for easier hovering)
  const HIT_AREA_SIZE = 24

  const dots = dotGroup
    .selectAll('g.meeting-icon')
    .data(meetings)
    .enter()
    .append('g')
    .attr('class', 'meeting-icon')
    .attr('transform', d => `translate(${x(d.date) - HIT_AREA_SIZE / 2}, ${rowCenter(d.institution_id) - HIT_AREA_SIZE / 2})`)
    .attr('tabindex', interactive ? 0 : -1)
    .style('cursor', interactive ? 'pointer' : 'default')
    .on('click', (event: MouseEvent, d: any) => {
      if (!interactive) return
      
      // For unauthorized meetings, show info toast instead of navigating
      // This prevents page refresh flicker and provides clear feedback
      if (d.authorized === false) {
        toast.info($t('Neturite prieigos prie šio posėdžio duomenų'))
        return
      }
      
      const routeFn = (window as any)?.route
      const url = routeFn ? routeFn('meetings.show', d.id) : `/admin/meetings/${d.id}`
      if (event.ctrlKey || event.metaKey || event.button === 1) {
        window.open(url, '_blank')
      } else {
        router.visit(url, { preserveScroll: false })
      }
    })
    .on('auxclick', (event: MouseEvent, d: any) => {
      if (!interactive || event.button !== 1) return
      
      // For unauthorized meetings, show info toast instead of navigating
      if (d.authorized === false) {
        toast.info($t('Neturite prieigos prie šio posėdžio duomenų'))
        return
      }
      
      event.preventDefault()
      const routeFn = (window as any)?.route
      const url = routeFn ? routeFn('meetings.show', d.id) : `/admin/meetings/${d.id}`
      window.open(url, '_blank')
    })
    .on('keydown', (event: KeyboardEvent, d: any) => {
      if (!interactive) return
      if (event.key === 'Enter' || event.key === ' ') {
        // For unauthorized meetings, show info toast instead of navigating
        if (d.authorized === false) {
          toast.info($t('Neturite prieigos prie šio posėdžio duomenų'))
          return
        }
        
        const routeFn = (window as any)?.route
        const url = routeFn ? routeFn('meetings.show', d.id) : `/admin/meetings/${d.id}`
        router.visit(url, { preserveScroll: false })
      }
    })

  // Add invisible hit area for easier hovering
  dots.append('rect')
    .attr('width', HIT_AREA_SIZE)
    .attr('height', HIT_AREA_SIZE)
    .attr('fill', 'transparent')
    .attr('rx', 4)

  // Add icon path (centered within hit area)
  // Icon varies by meeting type: in-person, remote, email, or calendar (fallback)
  const iconOffset = (HIT_AREA_SIZE - ICON_SIZE) / 2
  dots.append('path')
    .attr('d', (d: any) => {
      // Use type-aware icon selection
      return getMeetingIconPath(d.type_slug, d.completion_status, d.authorized)
    })
    .attr('transform', `translate(${iconOffset}, ${iconOffset}) scale(${ICON_SIZE / 20})`)
    .attr('fill', (d: any) => {
      // Unauthorized meetings get grey color
      if (d.authorized === false) {
        return colors.meetingUnauthorized
      }
      // Regular meetings use status-based colors
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
      if (d.authorized === false) {
        status = ' (unauthorized)'
      } else if (d.completion_status === 'complete') {
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
