import * as d3 from 'd3';

import type { GanttColors } from '@/Components/Graphs/ganttColors';

import { HANDLE_WIDTH_PX } from '../constants';
import { isActivePeriod, type TimelineColors } from '../timelineColors';
import type { ParsedRow, StagedDates, TimelineLayoutRow } from '../types';

export const BAR_INSET = 4;

export interface DutiableBarContext {
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  x: d3.ScaleTime<number, number>;
  colors: GanttColors;
  timelineColors: TimelineColors;
  layoutRows: TimelineLayoutRow[];
  innerWidth: number;
  selectedIds: Set<string>;
  /** Staged dates keyed by row id — drawn instead of the persisted ones. */
  staged: Map<string, StagedDates>;
  onSelect?: (row: ParsedRow, event: MouseEvent) => void;
  onHover?: (row: ParsedRow | null, event: MouseEvent) => void;
}

/** Whether a date sits on the first of its month, i.e. flush with a column edge. */
export function isMonthAligned(date: Date): boolean {
  return date.getDate() === 1;
}

/** Whether a date is the last day of its month — the aligned form for a term end. */
export function isMonthEndAligned(date: Date): boolean {
  return date.getDate() === new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
}

/**
 * One bar per assignment.
 *
 * Two things carry meaning beyond position:
 * - a bar that has already ended is muted, so the seats someone actually holds today
 *   stand out from the history behind them;
 * - a derived (ex-officio) bar is dashed and dimmed, because it mirrors its source and
 *   cannot be dragged;
 * - an edge that is *not* on a month boundary gets a notch and a dotted lead-in from
 *   the enclosing boundary, so "this date is deliberately the 18th" is visible at a
 *   glance rather than something you discover by opening the inspector.
 */
export function renderDutiableBars(ctx: DutiableBarContext): void {
  const { g, x, colors, timelineColors, layoutRows, innerWidth, selectedIds, staged, onSelect, onHover } = ctx;

  const bars = layoutRows.filter((r): r is TimelineLayoutRow & { row: ParsedRow } => r.row !== undefined);

  const layer = g.append('g').attr('class', 'dutiable-bars');

  const groups = layer.selectAll<SVGGElement, typeof bars[number]>('g.dutiable-bar')
    .data(bars, d => d.row.id)
    .join('g')
    .attr('class', 'dutiable-bar')
    .attr('data-row-id', d => d.row.id)
    .style('cursor', d => (d.row.editable ? 'grab' : 'pointer'));

  const resolved = (d: typeof bars[number]) => {
    const override = staged.get(d.row.id);

    if (!override) return { start: d.row.startDate, end: d.row.endDate };

    return {
      start: parseLocal(override.start_date),
      end: override.end_date ? parseLocal(override.end_date) : null,
    };
  };

  // An open-ended bar runs to the right edge; it is capped with an arrow rather than
  // a hard end so it never reads as "ends here".
  const barX = (d: typeof bars[number]) => x(resolved(d).start);
  const barW = (d: typeof bars[number]) => {
    const { start, end } = resolved(d);
    const right = end ? x(end) : innerWidth;

    return Math.max(2, right - x(start));
  };

  groups.selectAll('rect.bar-body')
    .data(d => [d])
    .join('rect')
    .attr('class', 'bar-body')
    .attr('x', barX)
    .attr('y', d => d.top + BAR_INSET / 2)
    .attr('width', barW)
    .attr('height', d => d.height - BAR_INSET)
    .attr('rx', 3)
    .attr('fill', d => barFill(d.row, timelineColors, staged.get(d.row.id)))
    // Selection wins the stroke; otherwise a cross-tenant representative is outlined, so
    // tenant and active/former can be read off the same bar at once.
    .attr('stroke', (d) => {
      if (selectedIds.has(d.row.id)) return colors.todayLine;

      return d.row.tenant_id !== null ? timelineColors.crossTenantStroke : 'none';
    })
    .attr('stroke-width', d => (selectedIds.has(d.row.id) ? 2 : (d.row.tenant_id !== null ? 1.5 : 0)))
    .attr('stroke-dasharray', d => (d.row.is_derived ? '4,3' : null))
    .attr('opacity', d => (d.row.is_derived ? 0.7 : 1));

  groups.selectAll('polygon.open-ended-cap')
    .data(d => (resolved(d).end === null ? [d] : []))
    .join('polygon')
    .attr('class', 'open-ended-cap')
    .attr('points', (d) => {
      const top = d.top + BAR_INSET / 2;
      const mid = top + (d.height - BAR_INSET) / 2;
      const right = innerWidth;

      return `${right - 8},${top} ${right},${mid} ${right - 8},${top + d.height - BAR_INSET}`;
    })
    .attr('fill', d => barFill(d.row, timelineColors, staged.get(d.row.id)))
    .attr('pointer-events', 'none');

  renderOffBoundaryMarks(groups, x, colors, resolved);
  renderEdgeHandles(groups, barX, barW);

  groups
    .on('click', (event: MouseEvent, d) => onSelect?.(d.row, event))
    .on('mousemove', (event: MouseEvent, d) => onHover?.(d.row, event))
    .on('mouseleave', (event: MouseEvent) => onHover?.(null, event));
}

/**
 * The grab targets for a resize. They carry `data-handle`, which is how the delegated
 * pointerdown listener tells an edge drag from a body drag without hit-testing pixels.
 *
 * An open-ended row gets its end handle at the arrow cap: grabbing it is how a term is
 * closed, which is otherwise a trip to the inspector.
 */
function renderEdgeHandles(
  groups: d3.Selection<SVGGElement, TimelineLayoutRow & { row: ParsedRow }, SVGGElement, unknown>,
  barX: (d: TimelineLayoutRow & { row: ParsedRow }) => number,
  barW: (d: TimelineLayoutRow & { row: ParsedRow }) => number,
): void {
  const edges: Array<{ handle: 'start' | 'end'; offset: (d: TimelineLayoutRow & { row: ParsedRow }) => number }> = [
    { handle: 'start', offset: d => barX(d) - HANDLE_WIDTH_PX / 2 },
    { handle: 'end', offset: d => barX(d) + barW(d) - HANDLE_WIDTH_PX / 2 },
  ];

  for (const edge of edges) {
    groups.selectAll(`rect.bar-handle-${edge.handle}`)
      .data(d => (d.row.editable ? [d] : []))
      .join('rect')
      .attr('class', `bar-handle-${edge.handle}`)
      .attr('data-handle', edge.handle)
      .attr('x', edge.offset)
      .attr('y', d => d.top)
      .attr('width', HANDLE_WIDTH_PX)
      .attr('height', d => d.height)
      .attr('fill', 'transparent')
      .style('cursor', 'ew-resize');
  }
}

function parseLocal(value: string): Date {
  const [year, month, day] = value.split('-').map(Number);

  return new Date(year, month - 1, day, 12, 0, 0);
}

/**
 * Status, in priority order: an unsaved edit outranks everything (it is what the user is
 * looking at), then whether the seat is still held, then ex-officio.
 *
 * Ended outranks ex-officio deliberately. Amber is a "this one is live and mirrored from
 * somewhere else" signal; on a seat that ended in 2019 it made history shout louder than
 * the present. The dashed stroke still says ex-officio either way.
 */
export function barFill(row: ParsedRow, colors: TimelineColors, staged: StagedDates | undefined): string {
  if (staged?.projected) return colors.projected;
  if (staged) return colors.staged;
  if (!isActivePeriod(row.endDate)) return colors.former;

  return row.is_derived ? colors.derived : colors.active;
}

/**
 * The notch + dotted lead-in that makes a non-boundary date legible on a month grid.
 * A boundary-aligned edge draws nothing, so the marks only ever mean "off the grid".
 */
function renderOffBoundaryMarks(
  groups: d3.Selection<SVGGElement, TimelineLayoutRow & { row: ParsedRow }, SVGGElement, unknown>,
  x: d3.ScaleTime<number, number>,
  colors: GanttColors,
  resolved: (d: TimelineLayoutRow & { row: ParsedRow }) => { start: Date; end: Date | null },
): void {
  groups.selectAll('g.off-boundary').remove();

  groups.each(function (d) {
    const { start, end } = resolved(d);
    const marks: Array<{ date: Date; anchor: Date }> = [];

    if (!isMonthAligned(start)) {
      marks.push({ date: start, anchor: new Date(start.getFullYear(), start.getMonth(), 1, 12) });
    }

    if (end && !isMonthEndAligned(end)) {
      marks.push({ date: end, anchor: new Date(end.getFullYear(), end.getMonth() + 1, 0, 12) });
    }

    if (marks.length === 0) return;

    const layer = d3.select(this).append('g').attr('class', 'off-boundary').attr('pointer-events', 'none');
    const top = d.top + BAR_INSET / 2;
    const bottom = top + d.height - BAR_INSET;

    for (const mark of marks) {
      layer.append('line')
        .attr('x1', x(mark.date))
        .attr('x2', x(mark.date))
        .attr('y1', top - 2)
        .attr('y2', bottom + 2)
        .attr('stroke', colors.yearMarker)
        .attr('stroke-width', 2);

      layer.append('line')
        .attr('x1', x(mark.anchor))
        .attr('x2', x(mark.date))
        .attr('y1', (top + bottom) / 2)
        .attr('y2', (top + bottom) / 2)
        .attr('stroke', colors.yearMarker)
        .attr('stroke-width', 1)
        .attr('stroke-dasharray', '2,2')
        .attr('opacity', 0.7);
    }
  });
}
