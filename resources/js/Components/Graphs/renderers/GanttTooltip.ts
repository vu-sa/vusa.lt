/**
 * GanttTooltip - Unified tooltip manager for the Gantt chart
 *
 * Provides a single tooltip element that displays context-aware content
 * based on what's being hovered (meetings, avatars, check-ins, or general date).
 *
 * This prevents multiple tooltips from appearing simultaneously.
 */
import * as d3 from 'd3';
import { trans as $t } from 'laravel-vue-i18n';

import type { GanttColors } from '../ganttColors';

import { ganttMeetingStatus } from './meetingStatus';

import { getAgendaItemStatus, type AgendaItemStatus, type AgendaItemType, type Vote } from '@/Composables/useAgendaItemStyling';

export type TooltipContentType = 'meeting' | 'member' | 'gap' | 'date' | 'request' | 'none';

export interface TooltipContent {
  type: TooltipContentType;
  html: string;
  /** Priority - higher priority content will override lower priority */
  priority: number;
}

export interface GanttTooltipManager {
  /** Show the tooltip with given content */
  show: (content: TooltipContent, x: number, y: number) => void;
  /** Hide the tooltip */
  hide: () => void;
  /** Update position without changing content */
  updatePosition: (x: number, y: number) => void;
  /** Check if currently showing a specific type */
  isShowingType: (type: TooltipContentType) => boolean;
  /** Get current content type */
  getCurrentType: () => TooltipContentType;
  /** Get the tooltip element for external styling */
  getElement: () => d3.Selection<HTMLDivElement, null, HTMLElement, unknown>;
}

/**
 * Create a unified tooltip manager
 */
export function createGanttTooltip(
  container: HTMLElement,
  colors: GanttColors,
): GanttTooltipManager {
  // Create single tooltip element
  const tooltip = d3.select(container)
    .selectAll<HTMLDivElement, null>('.gantt-unified-tooltip')
    .data([null])
    .join('div')
    .attr('class', 'gantt-unified-tooltip pointer-events-none absolute z-50 hidden text-xs ring-1 px-2 py-1')
    .style('background', colors.tooltipBg)
    .style('color', colors.tooltipText)
    .style('--tw-ring-color', colors.tooltipBorder)
    .style('max-width', '280px');

  let currentType: TooltipContentType = 'none';
  let currentPriority = 0;

  // 'date' and 'gap' are both produced by the same background mousemove
  // handler in renderHoverEffects.ts (the plain date tooltip becomes a gap
  // tooltip when the cursor is over a check-in, and back again as it moves
  // off). Priority exists to stop that ambient handler from overriding a
  // higher-priority meeting/member tooltip shown by its own hover — it must
  // not also stop the ambient handler from updating (including downgrading)
  // its own previous content, or the tooltip gets stuck showing stale gap
  // content until something else of equal-or-higher priority happens to fire.
  const AMBIENT_TYPES: TooltipContentType[] = ['date', 'gap'];

  return {
    show(content: TooltipContent, x: number, y: number) {
      const isAmbientSelfUpdate = AMBIENT_TYPES.includes(currentType) && AMBIENT_TYPES.includes(content.type);

      // Only show if higher or equal priority, or if not currently showing
      if (!isAmbientSelfUpdate && currentType !== 'none' && content.priority < currentPriority) {
        return;
      }

      currentType = content.type;
      currentPriority = content.priority;
      tooltip.html(content.html).classed('hidden', false);
      tooltip.style('left', `${x + 8}px`).style('top', `${y + 8}px`);
    },

    hide() {
      currentType = 'none';
      currentPriority = 0;
      tooltip.classed('hidden', true);
    },

    updatePosition(x: number, y: number) {
      if (currentType !== 'none') {
        tooltip.style('left', `${x + 8}px`).style('top', `${y + 8}px`);
      }
    },

    isShowingType(type: TooltipContentType) {
      return currentType === type;
    },

    getCurrentType() {
      return currentType;
    },

    getElement() {
      return tooltip;
    },
  };
}

/**
 * Content priority levels (higher = more important)
 */
export const TOOLTIP_PRIORITIES = {
  date: 1, // General date hover (lowest)
  gap: 2, // Check-in info
  member: 3, // Duty member avatar
  meeting: 3, // Meeting icon (same as member)
  request: 3, // Activity request chip
} as const;

/**
 * Build tooltip HTML for a meeting
 */
export function buildMeetingTooltipContent(
  meeting: {
    id: string | number;
    date: Date;
    institution_id: string | number;
    title?: string;
    type_slug?: string;
    completion_status?: string;
    vote_alignment_status?: 'all_match' | 'mixed' | 'all_mismatch' | 'neutral' | null;
    requires_student_perspective?: boolean | null;
    authorized?: boolean;
    has_report?: boolean;
    has_protocol?: boolean;
    has_calendar_event?: boolean;
    calendar_event_is_draft?: boolean;
    agenda_items?: Array<{
      id: string;
      title: string;
      type?: AgendaItemType;
      votes?: Vote[];
    }>;
    agenda_items_count?: number;
  },
  labelFor: (id: string | number) => string,
  fmt: Intl.DateTimeFormat,
  fmtDateOnly?: Intl.DateTimeFormat,
): TooltipContent {
  const name = labelFor(meeting.institution_id);
  const status = ganttMeetingStatus(meeting);
  const statusBadge = status ? `<span class="${status.tone}">${escapeHtml(status.label)}</span>` : '';

  // Build agenda items section if available (only for authorized meetings)
  let agendaHtml = '';
  if (meeting.authorized !== false && meeting.agenda_items && meeting.agenda_items.length > 0) {
    const totalCount = meeting.agenda_items_count ?? meeting.agenda_items.length;
    const itemsToShow = meeting.agenda_items.slice(0, 4);

    agendaHtml = `
      <div class="mt-1.5 pt-1.5 border-t border-current/20">
        <div class="text-[10px] uppercase tracking-wide opacity-60 mb-1">Darbotvarkė (${totalCount})</div>
        <div class="space-y-0.5">
          ${itemsToShow.map((item) => {
            const statusIcon = getAgendaItemStatusIcon(getAgendaItemStatus(item, meeting.requires_student_perspective ?? true));
            return `<div class="flex items-start gap-1.5 leading-tight">
              ${statusIcon}
              <span class="line-clamp-2 text-[11px]">${escapeHtml(item.title)}</span>
            </div>`;
          }).join('')}
        </div>
        ${totalCount > 4 ? `<div class="text-[10px] opacity-60 mt-1">+${totalCount - 4}</div>` : ''}
      </div>
    `;
  }

  // Build file status section (protocol and report indicators)
  const fileStatusHtml = buildFileStatusHtml(
    meeting.has_protocol,
    meeting.has_report,
    meeting.has_calendar_event,
    meeting.calendar_event_is_draft,
  );

  // Email meetings store start_time as 23:59 (deadline marker) — show date only
  // and use the institution name as the heading instead of a stored title that
  // historically embedded the time.
  const isEmail = meeting.type_slug === 'email';
  const dateFmt = isEmail && fmtDateOnly ? fmtDateOnly : fmt;
  const heading = isEmail ? name : (meeting.title ?? name);

  const html = `
    <div class="font-medium text-[12px] leading-tight flex items-center gap-1">
      ${heading} ${statusBadge}
    </div>
    <div class="opacity-80">${dateFmt.format(meeting.date)}</div>
    ${!isEmail && meeting.title ? `<div class="opacity-70">${name}</div>` : ''}
    ${fileStatusHtml}
    ${agendaHtml}
  `;

  return {
    type: 'meeting',
    html: html.trim(),
    priority: TOOLTIP_PRIORITIES.meeting,
  };
}

const CHECK_ICON = '<svg class="w-3 h-3 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
const CROSS_ICON = '<svg class="w-3 h-3 shrink-0 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>';
const MINUS_ICON = '<svg class="w-3 h-3 shrink-0 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>';
const SPLIT_ICON = '<svg class="w-3 h-3 shrink-0 text-status-info" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a7 7 0 100 14V3z" /><circle cx="10" cy="10" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5" /></svg>';
const DASHED_ICON = '<svg class="w-3 h-3 shrink-0 text-amber-500 dark:text-amber-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="3 2"><circle cx="10" cy="10" r="6" /></svg>';
const CLOCK_ICON = '<svg class="w-3 h-3 shrink-0 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>';
const INFO_ICON = '<svg class="w-3 h-3 shrink-0 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>';
const QUESTION_ICON = '<svg class="w-3 h-3 shrink-0 text-amber-500 dark:text-amber-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>';

/** Agenda item status icons, keyed by getAgendaItemStatus() so the tooltip agrees with the agenda. */
const agendaItemStatusIcons: Record<AgendaItemStatus, string> = {
  consensus: CHECK_ICON,
  student_aligned: CHECK_ICON,
  decision_positive: CHECK_ICON,
  student_misaligned: CROSS_ICON,
  decision_negative: CROSS_ICON,
  neutral_decided: MINUS_ICON,
  mixed: SPLIT_ICON,
  no_vote: DASHED_ICON,
  deferred: CLOCK_ICON,
  break: CLOCK_ICON,
  informational: INFO_ICON,
  withdrawn: MINUS_ICON,
  unset: QUESTION_ICON,
};

function getAgendaItemStatusIcon(status: AgendaItemStatus): string {
  return agendaItemStatusIcons[status];
}

/**
 * Escape HTML to prevent XSS in tooltips
 */
function escapeHtml(text: string): string {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Build file status HTML for protocol and report indicators
 * Uses ScrollText for protocol and FileBarChart for report icons
 * Green = has file, Amber = missing file
 */
function buildFileStatusHtml(
  hasProtocol?: boolean,
  hasReport?: boolean,
  hasCalendarEvent?: boolean,
  calendarEventIsDraft?: boolean,
): string {
  // Only show if we have any file status info
  if (hasProtocol === undefined && hasReport === undefined && hasCalendarEvent === undefined) {
    return '';
  }

  // Protocol icon (ScrollText from @lucide/vue)
  const protocolIcon = hasProtocol
    ? '<svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h12a2 2 0 0 0 2-2v-2H10v2a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v3h4"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M15 8h-5"/><path d="M15 12h-5"/></svg>'
    : '<svg class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M8 21h12a2 2 0 0 0 2-2v-2H10v2a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v3h4"/></svg>';

  // Report icon (FileBarChart from @lucide/vue)
  const reportIcon = hasReport
    ? '<svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 18v-2"/><path d="M12 18v-4"/><path d="M16 18v-6"/></svg>'
    : '<svg class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>';

  // Announced in the calendar. Same amber/green split as ShowMeeting.vue: amber is a draft
  // announcement, which nobody outside the admin can see. Absent entirely when not announced —
  // most meetings never are, so a permanent "missing" mark would be noise, unlike the files.
  const calendarHtml = hasCalendarEvent
    ? `<div class="flex items-center gap-1" title="${calendarEventIsDraft ? $t('meetings.announce.draft_hint') : $t('meetings.announce.published_hint')}">
        <svg class="w-3.5 h-3.5 ${calendarEventIsDraft ? 'text-amber-500 dark:text-amber-400' : 'text-green-600 dark:text-green-400'}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
      </div>`
    : '';

  return `
    <div class="flex items-center gap-2 mt-1.5 pt-1.5 border-t border-current/20">
      <div class="flex items-center gap-1" title="${hasProtocol ? 'Protokolas įkeltas' : 'Nėra protokolo'}">
        ${protocolIcon}
      </div>
      <div class="flex items-center gap-1" title="${hasReport ? 'Ataskaita įkelta' : 'Nėra ataskaitos'}">
        ${reportIcon}
      </div>
      ${calendarHtml}
    </div>
  `;
}

/**
 * Build tooltip HTML for a duty member
 */
export function buildMemberTooltipContent(
  member: { user: { name: string }; startDate: Date; endDate: Date | null },
  activityLabel?: string,
): TooltipContent {
  const startStr = member.startDate.toLocaleDateString();
  const endStr = member.endDate ? member.endDate.toLocaleDateString() : 'Present';

  let html = `
    <div class="font-medium text-[12px]">${member.user.name}</div>
    <div class="opacity-70">${startStr} → ${endStr}</div>
  `;

  // Add activity status if provided
  if (activityLabel) {
    html += `<div class="mt-1 text-[10px] opacity-80">${activityLabel}</div>`;
  }

  return {
    type: 'member',
    html: html.trim(),
    priority: TOOLTIP_PRIORITIES.member,
  };
}

/**
 * Build tooltip HTML for date hover (with optional check-in info)
 */
export function buildDateTooltipContent(
  institutionName: string,
  date: Date,
  fmtDateWithYear: Intl.DateTimeFormat,
  fmtDate: Intl.DateTimeFormat,
  gap?: { fromDate: Date; untilDate: Date; note?: string } | null,
): TooltipContent {
  let html = `
    <div class="font-medium text-[12px] leading-tight">${institutionName}</div>
    <div class="opacity-80">${fmtDateWithYear.format(date)}</div>
  `;

  if (gap) {
    const gapDateRange = `${fmtDate.format(gap.fromDate)} → ${fmtDate.format(gap.untilDate)}`;
    html += `
      <div class="mt-1.5 pt-1.5 border-t border-current/20">
        <div class="text-[10px] uppercase tracking-wide opacity-60 mb-0.5">Check-in</div>
        ${gap.note ? `<div class="line-clamp-2 text-[11px]">${gap.note}</div>` : ''}
        <div class="opacity-70 text-[10px]">${gapDateRange}</div>
      </div>
    `;
  }

  return {
    type: gap ? 'gap' : 'date',
    html: html.trim(),
    priority: gap ? TOOLTIP_PRIORITIES.gap : TOOLTIP_PRIORITIES.date,
  };
}
