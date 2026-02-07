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
    vote_alignment_status?: 'all_match' | 'mixed' | 'all_mismatch' | 'neutral';
    authorized?: boolean;
    has_report?: boolean;
    has_protocol?: boolean;
    agenda_items?: Array<{
      id: string;
      title: string;
      type?: 'voting' | 'informational' | 'deferred' | null;
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
  } else if (meeting.completion_status === 'no_items') {
    statusBadge = '<span class="text-zinc-500">(no agenda items)</span>'
  } else if (meeting.completion_status === 'incomplete') {
    statusBadge = '<span class="text-amber-600 dark:text-amber-400">(incomplete)</span>'
  } else if (meeting.completion_status === 'complete') {
    // Use alignment status for complete meetings
    switch (meeting.vote_alignment_status) {
      case 'all_match':
        statusBadge = '<span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400"><svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></span>'
        break
      case 'mixed':
        statusBadge = '<span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></span>'
        break
      case 'all_mismatch':
        statusBadge = '<span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400"><svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></span>'
        break
      default:
        statusBadge = '<span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400"><svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></span>'
    }
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
            const statusIcon = getAgendaItemStatusIcon(item.type, item.student_vote, item.decision)
            return `<div class="flex items-start gap-1.5 leading-tight">
              ${statusIcon}
              <span class="line-clamp-2 text-[11px]">${escapeHtml(item.title)}</span>
            </div>`
          }).join('')}
        </div>
        ${totalCount > 4 ? `<div class="text-[10px] opacity-60 mt-1">+${totalCount - 4} more</div>` : ''}
      </div>
    `
  }

  // Build file status section (protocol and report indicators)
  const fileStatusHtml = buildFileStatusHtml(meeting.has_protocol, meeting.has_report)

  const html = `
    <div class="font-medium text-[12px] leading-tight flex items-center gap-1">
      ${meeting.title ?? name} ${statusBadge}
    </div>
    <div class="opacity-80">${fmt.format(meeting.date)}</div>
    ${meeting.title ? `<div class="opacity-70">${name}</div>` : ''}
    ${fileStatusHtml}
    ${agendaHtml}
  `

  return {
    type: 'meeting',
    html: html.trim(),
    priority: TOOLTIP_PRIORITIES.meeting
  }
}

/**
 * Get agenda item status icon HTML based on type and vote data
 * Matches the 7 statuses from useAgendaItemStyling composable:
 * - student_aligned: green checkmark (voting, student_vote === decision)
 * - student_misaligned: red X (voting, student_vote !== decision)
 * - neutral_decided: gray minus (voting, decision is neutral)
 * - no_vote: amber dashed circle (voting but no vote data)
 * - deferred: gray clock (type = deferred)
 * - informational: gray info (type = informational)
 * - unset: amber question mark (type is null)
 */
function getAgendaItemStatusIcon(
  type?: 'voting' | 'informational' | 'deferred' | null,
  studentVote?: 'positive' | 'negative' | 'neutral' | null,
  decision?: 'positive' | 'negative' | 'neutral' | null
): string {
  // Type-based statuses first
  if (type === 'deferred') {
    // Deferred - gray clock
    return '<svg class="w-3 h-3 shrink-0 text-zinc-400 dark:text-zinc-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>'
  }
  
  if (type === 'informational') {
    // Informational - gray info
    return '<svg class="w-3 h-3 shrink-0 text-zinc-400 dark:text-zinc-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>'
  }
  
  if (type === null || type === undefined) {
    // Unset type - amber question mark
    return '<svg class="w-3 h-3 shrink-0 text-amber-500 dark:text-amber-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>'
  }
  
  // Type is 'voting' - check vote data
  if (!studentVote || !decision) {
    // No vote data - amber dashed circle
    return '<svg class="w-3 h-3 shrink-0 text-amber-500 dark:text-amber-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="3 2"><circle cx="10" cy="10" r="6" /></svg>'
  }
  
  // Decision is neutral
  if (decision === 'neutral') {
    // Neutral decided - gray minus
    return '<svg class="w-3 h-3 shrink-0 text-zinc-500 dark:text-zinc-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>'
  }
  
  // Check alignment
  if (studentVote === decision) {
    // Aligned - green checkmark
    return '<svg class="w-3 h-3 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>'
  }
  
  // Misaligned - red X
  return '<svg class="w-3 h-3 shrink-0 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>'
}

/**
 * @deprecated Use getAgendaItemStatusIcon instead
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
 * Build file status HTML for protocol and report indicators
 * Uses ScrollText for protocol and FileBarChart for report icons
 * Green = has file, Amber = missing file
 */
function buildFileStatusHtml(hasProtocol?: boolean, hasReport?: boolean): string {
  // Only show if we have any file status info
  if (hasProtocol === undefined && hasReport === undefined) {
    return ''
  }

  // Protocol icon (ScrollText from lucide-vue-next)
  const protocolIcon = hasProtocol
    ? '<svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h12a2 2 0 0 0 2-2v-2H10v2a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v3h4"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M15 8h-5"/><path d="M15 12h-5"/></svg>'
    : '<svg class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M8 21h12a2 2 0 0 0 2-2v-2H10v2a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v3h4"/></svg>'

  // Report icon (FileBarChart from lucide-vue-next)
  const reportIcon = hasReport
    ? '<svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 18v-2"/><path d="M12 18v-4"/><path d="M16 18v-6"/></svg>'
    : '<svg class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>'

  return `
    <div class="flex items-center gap-2 mt-1.5 pt-1.5 border-t border-current/20">
      <div class="flex items-center gap-1" title="${hasProtocol ? 'Protokolas įkeltas' : 'Nėra protokolo'}">
        ${protocolIcon}
      </div>
      <div class="flex items-center gap-1" title="${hasReport ? 'Ataskaita įkelta' : 'Nėra ataskaitos'}">
        ${reportIcon}
      </div>
    </div>
  `
}

/**
 * Build tooltip HTML for a duty member
 */
export function buildMemberTooltipContent(
  member: { user: { name: string }; startDate: Date; endDate: Date | null },
  activityLabel?: string
): TooltipContent {
  const startStr = member.startDate.toLocaleDateString()
  const endStr = member.endDate ? member.endDate.toLocaleDateString() : 'Present'
  
  let html = `
    <div class="font-medium text-[12px]">${member.user.name}</div>
    <div class="opacity-70">${startStr} → ${endStr}</div>
  `
  
  // Add activity status if provided
  if (activityLabel) {
    html += `<div class="mt-1 text-[10px] opacity-80">${activityLabel}</div>`
  }

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
