/**
 * renderHoverEffects - Render interactive hover effects and click-to-create functionality
 *
 * Renders hover line, create circle affordance, and handles mouse
 * events for the interactive Gantt chart. Uses unified tooltip system.
 */
import * as d3 from 'd3';

import type { GanttColors } from '../ganttColors';

import type { GanttTooltipManager } from './GanttTooltip';
import { buildDateTooltipContent } from './GanttTooltip';

interface LayoutRow {
  key: string | number;
  type: 'tenant' | 'institution';
  tenantId?: string | number;
  institutionId?: string | number;
  top: number;
  height: number;
}

interface ParsedMeeting {
  id: string | number;
  date: Date;
  institution_id: string | number;
}

interface ParsedGap {
  institution_id: string | number;
  fromDate: Date;
  untilDate: Date;
  note?: string;
}

export interface HoverEffectsRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  /** Container element for positioning */
  container: HTMLElement;
  /** Time scale */
  x: d3.ScaleTime<number, number>;
  /** Inner height of SVG */
  innerHeight: number;
  /** Layout rows with positions */
  layoutRows: LayoutRow[];
  /** Filtered meetings for snapping */
  meetings: ParsedMeeting[];
  /** Filtered gaps for tooltip info */
  gaps: ParsedGap[];
  /** Color palette */
  colors: GanttColors;
  /** Get row top position by key */
  rowTop: (key: string | number) => number;
  /** Get row height by key */
  rowHeightFor: (key: string | number) => number;
  /** Get row center by key */
  rowCenter: (key: string | number) => number;
  /** Get label for institution */
  labelFor: (id: string | number) => string;
  /** Date formatter with year */
  fmtDateWithYear: Intl.DateTimeFormat;
  /** Date formatter without year */
  fmtDate: Intl.DateTimeFormat;
  /** Whether interactive (clickable) */
  interactive: boolean;
  /** Unified tooltip manager */
  tooltipManager?: GanttTooltipManager;
  /** Callback when create-meeting is triggered */
  onCreateMeeting?: (payload: { institution_id: string | number; suggestedAt: Date }) => void;
}

/**
 * Render hover effects and click-to-create functionality
 */
export function renderHoverEffects(ctx: HoverEffectsRenderContext): void {
  const {
    g, container, x, innerHeight, layoutRows, meetings, gaps, colors,
    rowTop, rowHeightFor, rowCenter, labelFor, fmtDateWithYear, fmtDate,
    interactive, tooltipManager, onCreateMeeting,
  } = ctx;

  // Hover indicator line across the chart (full-height)
  const hoverLine = g.append('line')
    .attr('class', 'hover-line')
    .attr('y1', 0)
    .attr('y2', innerHeight)
    .attr('stroke', colors.hoverLine)
    .attr('stroke-dasharray', '2,2')
    .attr('opacity', 0)
    .attr('pointer-events', 'none');

  // Dashed circle affordance for creating a meeting on hovered row/day
  const createCircle = g.append('circle')
    .attr('class', 'create-circle')
    .attr('r', 7)
    .attr('fill', 'none')
    .attr('stroke', colors.hoverCircle)
    .attr('stroke-dasharray', '4,3')
    .attr('opacity', 0)
    .attr('pointer-events', 'none');

  // Index meetings by row for snapping
  const meetingsByRow = new Map<string | number, { x: number; d: ParsedMeeting }[]>();
  for (const m of meetings) {
    const k = m.institution_id;
    const arr = meetingsByRow.get(k) ?? [];
    arr.push({ x: x(m.date), d: m });
    meetingsByRow.set(k, arr);
  }
  for (const arr of meetingsByRow.values()) arr.sort((a, b) => a.x - b.x);

  // Index gaps (check-ins) by institution for quick lookup
  const gapsByRow = new Map<string | number, ParsedGap[]>();
  for (const gap of gaps) {
    const k = gap.institution_id;
    const arr = gapsByRow.get(k) ?? [];
    arr.push(gap);
    gapsByRow.set(k, arr);
  }

  // Helper to find active gap for a given institution and date
  const findActiveGap = (institutionId: string | number, date: Date) => {
    const rowGaps = gapsByRow.get(institutionId) ?? [];
    return rowGaps.find(gap => date >= gap.fromDate && date <= gap.untilDate);
  };

  // Find row by Y position
  const findRowByY = (my: number): LayoutRow | undefined => {
    for (const lr of layoutRows) {
      if (my >= lr.top && my < lr.top + lr.height) {
        return lr;
      }
    }
    return undefined;
  };

  // Check if target is within meeting icons or duty member markers (to prevent double tooltips)
  const isOverInteractiveElement = (target: EventTarget | null): boolean => {
    if (!target || !(target instanceof Element)) return false;
    const meetingIcons = g.select('.meeting-icons').node() as Element | null;
    const dutyMarkers = g.select('.duty-member-markers').node() as Element | null;
    return (meetingIcons ? meetingIcons.contains(target) : false)
      || (dutyMarkers ? dutyMarkers.contains(target) : false);
  };

  // Single mousemove handler: snap to nearest meeting dot in row (within threshold), else center of day
  g.on('mousemove', function (event) {
    const [mx, my] = d3.pointer(event, this as any);
    const dayStart = d3.timeDay.floor(x.invert(mx));

    // Hide create circle if hovering over interactive elements (meetings, avatars)
    // The individual renderers will show their own tooltip content via the manager
    if (isOverInteractiveElement(event.target)) {
      createCircle.attr('opacity', 0);
      // Still show the hover line for visual feedback
      return;
    }

    if (my < 0 || my > innerHeight || layoutRows.length === 0) {
      createCircle.attr('opacity', 0);
      if (tooltipManager) tooltipManager.hide();
      hoverLine.attr('opacity', 0);
      return;
    }

    const lr = findRowByY(my);
    if (!lr) return;

    if (lr.type === 'tenant') {
      createCircle.attr('opacity', 0);
      if (tooltipManager) tooltipManager.hide();
      hoverLine.attr('opacity', 0);
      return;
    }

    const rowId = lr.institutionId!;
    const dayEnd = d3.timeDay.offset(dayStart, 1);
    let centerX = (x(dayStart) + x(dayEnd)) / 2;
    let circleR = 7;

    // Snap to nearest meeting if within threshold
    const rowMeetings = meetingsByRow.get(rowId) || [];
    if (rowMeetings.length) {
      // Binary search to find nearest meeting
      let lo = 0, hi = rowMeetings.length - 1;
      while (lo < hi) {
        const mid = Math.floor((lo + hi) / 2);
        const midVal = rowMeetings[mid];
        if (!midVal) break;
        if (midVal.x < mx) lo = mid + 1;
        else hi = mid;
      }
      const candA = rowMeetings[lo];
      const candB = rowMeetings[Math.max(0, lo - 1)];
      const candidates = [candA, candB].filter((c): c is { x: number; d: ParsedMeeting } => !!c);
      let best = candidates.length ? candidates[0] : undefined;
      if (candidates.length > 1 && candidates[0] && candidates[1]) {
        if (Math.abs(candidates[1].x - mx) < Math.abs(candidates[0].x - mx)) {
          best = candidates[1];
        }
      }
      const dist = best ? Math.abs(best.x - mx) : Infinity;
      const snapThreshold = 8;
      if (best && dist <= snapThreshold) {
        centerX = best.x;
        circleR = 4;
      }
    }

    hoverLine
      .attr('x1', centerX)
      .attr('x2', centerX)
      .attr('y1', 0)
      .attr('y2', innerHeight)
      .attr('opacity', 1);

    // Show create affordance only when interactive
    createCircle
      .attr('cx', centerX)
      .attr('cy', rowCenter(lr.key))
      .attr('r', circleR)
      .attr('opacity', interactive ? 1 : 0);

    // Build tooltip HTML with optional check-in info
    const activeGap = findActiveGap(rowId, dayStart);

    if (interactive && tooltipManager) {
      const rect = container.getBoundingClientRect();
      const content = buildDateTooltipContent(labelFor(rowId), dayStart, fmtDateWithYear, fmtDate, activeGap);
      tooltipManager.show(content, event.clientX - rect.left, event.clientY - rect.top);
    }
  });

  g.on('mouseleave', () => {
    hoverLine.attr('opacity', 0);
    createCircle.attr('opacity', 0);
    if (tooltipManager) tooltipManager.hide();
  });

  // Click-to-create only when not clicking on dots or gaps
  g.on('click', function (event) {
    if (!interactive || !onCreateMeeting) return;
    if (event.shiftKey) return;

    const target = event.target as Node;
    // Select the meeting icons group created by renderMeetings
    const dotNode = g.select('.meeting-icons').node() as Element | null;
    const gapNode = g.select('.gap-lines').node() as Element | null;
    if ((dotNode && dotNode.contains(target)) || (gapNode && gapNode.contains(target))) {
      return;
    }

    const [mx, my] = d3.pointer(event, this as any);
    if (my < 0 || my > innerHeight || layoutRows.length === 0) return;

    const day = d3.timeDay.floor(x.invert(mx));
    // Set time to 12:00 noon (more reasonable default than midnight)
    day.setHours(12, 0, 0, 0);
    const lr = findRowByY(my);
    if (!lr || lr.type !== 'institution') return;

    onCreateMeeting({ institution_id: lr.institutionId!, suggestedAt: day });
  });
}
