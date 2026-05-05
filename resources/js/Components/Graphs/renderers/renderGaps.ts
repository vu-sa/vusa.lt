/**
 * renderGaps - Render check-in periods on the Gantt chart
 *
 * Renders check-in periods as striped rectangles with CalendarOff icons at both ends.
 * The visual design indicates "no planned meetings during this time" clearly.
 */
import type * as d3 from 'd3';

import type { GanttColors } from '../ganttColors';

interface ParsedGap {
  institution_id: string | number;
  fromDate: Date;
  untilDate: Date;
  mode?: 'heads_up' | 'no_meetings';
  note?: string;
}

export interface GapRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  /** Time scale */
  x: d3.ScaleTime<number, number>;
  /** Gaps data */
  gaps: ParsedGap[];
  /** Color palette */
  colors: GanttColors;
  /** Get row center Y position */
  rowCenter: (key: string | number) => number;
  /** Get row top Y position */
  rowTop: (key: string | number) => number;
  /** Get row height */
  rowHeightFor: (key: string | number) => number;
  /** Callback when gap is clicked (to create meeting) */
  onCreateMeeting?: (payload: { institution_id: string | number; suggestedAt: Date }) => void;
}

// Lucide CalendarOff icon path (24x24 viewBox)
// This icon represents "no meetings scheduled" - a calendar with a diagonal line through it
const CALENDAR_OFF_PATH = 'M4.18 4.18A2 2 0 0 0 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 1.82-1.18M21 15.5V6a2 2 0 0 0-2-2H9.5M16 2v4M3 10h7M21 10h-5.5M8 2v4M2 2l20 20';

// Icon size for the CalendarOff markers
const ICON_SIZE = 12;

/**
 * Render check-in/gap periods with striped rectangles and CalendarOff icons
 */
export function renderGaps(ctx: GapRenderContext): void {
  const { g, x, gaps, colors, rowCenter, rowTop, rowHeightFor, onCreateMeeting } = ctx;

  const gapGroup = g.append('g').attr('class', 'gap-lines');

  // Calculate rectangle height (slightly smaller than row height for padding)
  const getRectHeight = (institutionId: string | number) => {
    const rowH = rowHeightFor(institutionId);
    return Math.min(rowH - 6, 18); // Max 18px height, with 3px padding top/bottom
  };

  const getRectY = (institutionId: string | number) => {
    const rowH = rowHeightFor(institutionId);
    const rectH = getRectHeight(institutionId);
    return rowTop(institutionId) + (rowH - rectH) / 2;
  };

  // Render striped rectangles for check-in periods
  gapGroup
    .selectAll('rect.check-in-bar')
    .data(gaps)
    .enter()
    .append('rect')
    .attr('class', 'check-in-bar')
    .attr('x', d => x(d.fromDate))
    .attr('y', d => getRectY(d.institution_id))
    .attr('width', d => Math.max(0, x(d.untilDate) - x(d.fromDate)))
    .attr('height', d => getRectHeight(d.institution_id))
    .attr('fill', 'url(#checkInStripes)')
    .attr('stroke', colors.checkInStroke)
    .attr('stroke-width', 1)
    .attr('rx', 4)
    .attr('ry', 4)
    .style('cursor', onCreateMeeting ? 'pointer' : 'default')
    .on('click', (event, d: any) => {
      if (onCreateMeeting) {
        // Suggest meeting at midpoint of check-in period, with time set to 12:00 noon
        const midTime = new Date((d.fromDate.getTime() + d.untilDate.getTime()) / 2);
        midTime.setHours(12, 0, 0, 0);
        onCreateMeeting({ institution_id: d.institution_id, suggestedAt: midTime });
      }
    })
    .append('title')
    .text((d) => {
      const dateRange = `${d.fromDate.toLocaleDateString()} → ${d.untilDate.toLocaleDateString()}`;
      return d.note ? `${d.note}\n${dateRange}` : `Check-in: ${dateRange}`;
    });

  // Create icon groups for start icons (CalendarOff at the beginning)
  const startIconGroups = gapGroup
    .selectAll('g.check-in-start-icon')
    .data(gaps)
    .enter()
    .append('g')
    .attr('class', 'check-in-start-icon')
    .attr('transform', (d) => {
      const xPos = x(d.fromDate) - ICON_SIZE / 2;
      const yPos = rowCenter(d.institution_id) - ICON_SIZE / 2;
      return `translate(${xPos}, ${yPos})`;
    })
    .style('pointer-events', 'none');

  // Render start icons
  startIconGroups
    .append('path')
    .attr('d', CALENDAR_OFF_PATH)
    .attr('fill', 'none')
    .attr('stroke', colors.checkInIcon)
    .attr('stroke-width', 1.5)
    .attr('stroke-linecap', 'round')
    .attr('stroke-linejoin', 'round')
    .attr('opacity', colors.checkInIconOpacity)
    .attr('transform', `scale(${ICON_SIZE / 24})`);

  // Create icon groups for end icons (CalendarOff at the end)
  const endIconGroups = gapGroup
    .selectAll('g.check-in-end-icon')
    .data(gaps)
    .enter()
    .append('g')
    .attr('class', 'check-in-end-icon')
    .attr('transform', (d) => {
      const xPos = x(d.untilDate) - ICON_SIZE / 2;
      const yPos = rowCenter(d.institution_id) - ICON_SIZE / 2;
      return `translate(${xPos}, ${yPos})`;
    })
    .style('pointer-events', 'none');

  // Render end icons
  endIconGroups
    .append('path')
    .attr('d', CALENDAR_OFF_PATH)
    .attr('fill', 'none')
    .attr('stroke', colors.checkInIcon)
    .attr('stroke-width', 1.5)
    .attr('stroke-linecap', 'round')
    .attr('stroke-linejoin', 'round')
    .attr('opacity', colors.checkInIconOpacity)
    .attr('transform', `scale(${ICON_SIZE / 24})`);
}
