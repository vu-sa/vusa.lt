/**
 * GanttTooltip - Unified tooltip manager for the Gantt chart
 * 
 * Provides a single tooltip element that displays context-aware content
 * based on what's being hovered (meetings, avatars, check-ins, or general date).
 * 
 * This prevents multiple tooltips from appearing simultaneously.
 */
import * as d3 from 'd3'
import type { GanttColors } from '../ganttColors'

export type TooltipContentType = 'meeting' | 'member' | 'gap' | 'date' | 'none'

export interface TooltipContent {
  type: TooltipContentType
  html: string
  /** Priority - higher priority content will override lower priority */
  priority: number
}

export interface GanttTooltipManager {
  /** Show the tooltip with given content */
  show: (content: TooltipContent, x: number, y: number) => void
  /** Hide the tooltip */
  hide: () => void
  /** Update position without changing content */
  updatePosition: (x: number, y: number) => void
  /** Check if currently showing a specific type */
  isShowingType: (type: TooltipContentType) => boolean
  /** Get current content type */
  getCurrentType: () => TooltipContentType
  /** Get the tooltip element for external styling */
  getElement: () => d3.Selection<HTMLDivElement, null, HTMLElement, unknown>
}

/**
 * Create a unified tooltip manager
 */
export function createGanttTooltip(
  container: HTMLElement,
  colors: GanttColors
): GanttTooltipManager {
  // Create single tooltip element
  const tooltip = d3.select(container)
    .selectAll<HTMLDivElement, null>('.gantt-unified-tooltip')
    .data([null])
    .join('div')
    .attr('class', 'gantt-unified-tooltip pointer-events-none absolute z-50 hidden text-[11px] shadow-sm ring-1 rounded px-2 py-1')
    .style('background', colors.tooltipBg)
    .style('color', colors.tooltipText)
    .style('--tw-ring-color', colors.tooltipBorder)
    .style('max-width', '280px')

  let currentType: TooltipContentType = 'none'
  let currentPriority = 0

  return {
    show(content: TooltipContent, x: number, y: number) {
      // Only show if higher or equal priority, or if not currently showing
      if (currentType !== 'none' && content.priority < currentPriority) {
        return
      }

      currentType = content.type
      currentPriority = content.priority
      tooltip.html(content.html).classed('hidden', false)
      tooltip.style('left', `${x + 8}px`).style('top', `${y + 8}px`)
    },

    hide() {
      currentType = 'none'
      currentPriority = 0
      tooltip.classed('hidden', true)
    },

    updatePosition(x: number, y: number) {
      if (currentType !== 'none') {
        tooltip.style('left', `${x + 8}px`).style('top', `${y + 8}px`)
      }
    },

    isShowingType(type: TooltipContentType) {
      return currentType === type
    },

    getCurrentType() {
      return currentType
    },

    getElement() {
      return tooltip
    }
  }
}

/**
 * Content priority levels (higher = more important)
 */
export const TOOLTIP_PRIORITIES = {
  date: 1,      // General date hover (lowest)
  gap: 2,       // Check-in info
  member: 3,    // Duty member avatar
  meeting: 3,   // Meeting icon (same as member)
} as const

/**
 * Build tooltip HTML for a meeting
 */
export function buildMeetingTooltipContent(
  meeting: { 
    id: string | number; 
    date: Date; 
    institution_id: string | number; 
    title?: string; 
    completion_status?: string;
    authorized?: boolean;
    agenda_items?: Array<{
      id: string;
      title: string;
      student_vote?: 'positive' | 'negative' | 'neutral' | null;
      decision?: 'positive' | 'negative' | 'neutral' | null;
    }>;
    agenda_items_count?: number;
  },
  labelFor: (id: string | number) => string,
  fmt: Intl.DateTimeFormat
): TooltipContent {
  const name = labelFor(meeting.institution_id)
  let statusBadge = ''
  
  // Unauthorized meetings show a special badge
  if (meeting.authorized === false) {
    statusBadge = '<span class="text-zinc-500 dark:text-zinc-400">(unauthorized)</span>'
  } else if (meeting.completion_status === 'complete') {
    statusBadge = '<span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400"><svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></span>'
  } else if (meeting.completion_status === 'no_items') {
    statusBadge = '<span class="text-zinc-500">(no agenda items)</span>'
  } else if (meeting.completion_status === 'incomplete') {
    statusBadge = '<span class="text-amber-600 dark:text-amber-400">(incomplete)</span>'
  }

  // Build agenda items section if available (only for authorized meetings)
  let agendaHtml = ''
  if (meeting.authorized !== false && meeting.agenda_items && meeting.agenda_items.length > 0) {
    const totalCount = meeting.agenda_items_count ?? meeting.agenda_items.length
    const itemsToShow = meeting.agenda_items.slice(0, 4)
    
    agendaHtml = `
      <div class="mt-1.5 pt-1.5 border-t border-current/20">
        <div class="text-[10px] uppercase tracking-wide opacity-60 mb-1">Darbotvarkė (${totalCount})</div>
        <div class="space-y-0.5">
          ${itemsToShow.map(item => {
            const outcomeIcon = getVoteOutcomeIcon(item.student_vote, item.decision)
            return `<div class="flex items-start gap-1.5 leading-tight">
              ${outcomeIcon}
              <span class="line-clamp-2 text-[11px]">${escapeHtml(item.title)}</span>
            </div>`
          }).join('')}
        </div>
        ${totalCount > 4 ? `<div class="text-[10px] opacity-60 mt-1">+${totalCount - 4} more</div>` : ''}
      </div>
    `
  }

  const html = `
    <div class="font-medium text-[12px] leading-tight flex items-center gap-1">
      ${meeting.title ?? name} ${statusBadge}
    </div>
    <div class="opacity-80">${fmt.format(meeting.date)}</div>
    ${meeting.title ? `<div class="opacity-70">${name}</div>` : ''}
    ${agendaHtml}
  `

  return {
    type: 'meeting',
    html: html.trim(),
    priority: TOOLTIP_PRIORITIES.meeting
  }
}

/**
 * Get vote outcome icon HTML based on student_vote and decision
 * Returns: checkmark (match), cross (mismatch), question mark (incomplete data)
 */
function getVoteOutcomeIcon(
  studentVote?: 'positive' | 'negative' | 'neutral' | null,
  decision?: 'positive' | 'negative' | 'neutral' | null
): string {
  // Both votes present - check if they match
  if (studentVote && decision) {
    if (studentVote === decision) {
      // Match - green checkmark
      return '<svg class="w-3 h-3 shrink-0 text-green-600 dark:text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>'
    } else {
      // Mismatch - amber cross
      return '<svg class="w-3 h-3 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>'
    }
  }
  
  // Only one vote present - show question mark (incomplete)
  if (studentVote || decision) {
    return '<svg class="w-3 h-3 shrink-0 text-zinc-400 dark:text-zinc-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>'
  }
  
  // No vote data - neutral dot
  return '<svg class="w-3 h-3 shrink-0 text-zinc-300 dark:text-zinc-600" viewBox="0 0 20 20" fill="currentColor"><circle cx="10" cy="10" r="3" /></svg>'
}

/**
 * Escape HTML to prevent XSS in tooltips
 */
function escapeHtml(text: string): string {
  const div = document.createElement('div')
  div.textContent = text
  return div.innerHTML
}

/**
 * Build tooltip HTML for a duty member
 */
export function buildMemberTooltipContent(
  member: { user: { name: string }; startDate: Date; endDate: Date | null }
): TooltipContent {
  const startStr = member.startDate.toLocaleDateString()
  const endStr = member.endDate ? member.endDate.toLocaleDateString() : 'Present'
  const html = `
    <div class="font-medium text-[12px]">${member.user.name}</div>
    <div class="opacity-70">${startStr} → ${endStr}</div>
  `

  return {
    type: 'member',
    html: html.trim(),
    priority: TOOLTIP_PRIORITIES.member
  }
}

/**
 * Build tooltip HTML for date hover (with optional check-in info)
 */
export function buildDateTooltipContent(
  institutionName: string,
  date: Date,
  fmtDateWithYear: Intl.DateTimeFormat,
  fmtDate: Intl.DateTimeFormat,
  gap?: { fromDate: Date; untilDate: Date; note?: string } | null
): TooltipContent {
  let html = `
    <div class="font-medium text-[12px] leading-tight">${institutionName}</div>
    <div class="opacity-80">${fmtDateWithYear.format(date)}</div>
  `

  if (gap) {
    const gapDateRange = `${fmtDate.format(gap.fromDate)} → ${fmtDate.format(gap.untilDate)}`
    html += `
      <div class="mt-1.5 pt-1.5 border-t border-current/20">
        <div class="text-[10px] uppercase tracking-wide opacity-60 mb-0.5">Check-in</div>
        ${gap.note ? `<div class="line-clamp-2 text-[11px]">${gap.note}</div>` : ''}
        <div class="opacity-70 text-[10px]">${gapDateRange}</div>
      </div>
    `
  }

  return {
    type: gap ? 'gap' : 'date',
    html: html.trim(),
    priority: gap ? TOOLTIP_PRIORITIES.gap : TOOLTIP_PRIORITIES.date
  }
}
