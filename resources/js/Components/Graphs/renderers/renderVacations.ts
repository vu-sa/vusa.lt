/**
 * renderVacations - Render vacation period background bands
 *
 * Renders semi-transparent colored bands for vacation periods
 * (summer, winter, easter) to provide visual context.
 *
 * Split into two phases so callers can sandwich other content between them:
 * - `renderVacationBackgrounds` (Layer 1): an opaque solid rect that must
 *   render early, right after the row background, to properly cover the
 *   Sunday/zebra grid underneath.
 * - `renderVacationOverlay` (Layers 2-3): the translucent colored tint and
 *   its border lines. These should render LAST — after meetings, gaps, and
 *   duty avatars — so the vacation hue reads consistently on top instead of
 *   blending with e.g. a meeting's green safety band that happens to fall
 *   inside the period (previously: amber summer vacation could look
 *   greenish wherever a safety band overlapped it).
 */
import type * as d3 from 'd3';

import type { GanttColors } from '../ganttColors';

import type { VacationPeriod } from '@/Composables/useVacationPeriods';

interface LayoutRow {
  key: string | number;
  type: 'tenant' | 'institution';
  height: number;
}

export interface VacationRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  /** Time scale */
  x: d3.ScaleTime<number, number>;
  /** Layout rows */
  layoutRows: LayoutRow[];
  /** Inner width */
  innerWidth: number;
  /** Minimum time */
  minTime: Date;
  /** Maximum time */
  maxTime: Date;
  /** Vacation periods loaded from the backend (see useVacationPeriods) */
  vacationPeriods: VacationPeriod[];
  /** Color palette */
  colors: GanttColors;
  /** Get row top position */
  rowTop: (key: string | number) => number;
  /** Get row height */
  rowHeightFor: (key: string | number) => number;
}

interface VacationBand {
  period: VacationPeriod;
  row: LayoutRow;
}

/** Shared setup: visible periods, institution rows, and the period × row cross product. */
function computeVacationBands(ctx: VacationRenderContext): {
  vacationPeriods: VacationPeriod[];
  institutionRows: LayoutRow[];
  vacationBands: VacationBand[];
} {
  const { layoutRows, minTime, maxTime } = ctx;

  const vacationPeriods = ctx.vacationPeriods.filter(
    period => period.start <= maxTime && period.end >= minTime,
  );
  const institutionRows = layoutRows.filter(r => r.type === 'institution');

  const vacationBands: VacationBand[] = [];
  for (const period of vacationPeriods) {
    for (const row of institutionRows) {
      vacationBands.push({ period, row });
    }
  }

  return { vacationPeriods, institutionRows, vacationBands };
}

/**
 * Layer 1 only: opaque solid background covering the Sunday/zebra grid.
 * Must render early (right after the row background) so later content
 * (meetings, gaps, avatars) still draws on top and stays visible.
 */
export function renderVacationBackgrounds(ctx: VacationRenderContext): void {
  const { g, x, innerWidth, colors, rowTop, rowHeightFor } = ctx;
  const { vacationBands } = computeVacationBands(ctx);

  if (vacationBands.length === 0) return;

  g.append('g')
    .attr('class', 'vacation-solid-backgrounds')
    .selectAll('rect')
    .data(vacationBands)
    .enter()
    .append('rect')
    .attr('class', 'vacation-solid-bg')
    .attr('x', d => Math.max(0, x(d.period.start)))
    .attr('y', d => rowTop(d.row.key))
    .attr('width', (d) => {
      const startX = Math.max(0, x(d.period.start));
      const endX = Math.min(innerWidth, x(d.period.end));
      return Math.max(0, endX - startX);
    })
    .attr('height', d => rowHeightFor(d.row.key))
    .attr('fill', colors.vacationSolidBg ?? colors.axisBg)
    .attr('pointer-events', 'none');
}

/**
 * Layers 2-3: translucent colored overlay + border lines. Render this LAST
 * in the draw order (after meetings/gaps/avatars) so the vacation tint reads
 * on top of anything that happens to fall inside the period.
 */
export function renderVacationOverlay(ctx: VacationRenderContext): void {
  const { g, x, innerWidth, colors, rowTop, rowHeightFor } = ctx;
  const { vacationPeriods, institutionRows, vacationBands } = computeVacationBands(ctx);

  if (vacationBands.length === 0) return;

  const vacationGroup = g.append('g').attr('class', 'vacation-overlays');

  // Helper to get stroke color for vacation type
  const getStrokeColor = (type: VacationPeriod['type']) => {
    switch (type) {
      case 'summer': return colors.vacationSummerStroke ?? colors.vacationSummer;
      case 'winter': return colors.vacationWinterStroke ?? colors.vacationWinter;
      case 'easter': return colors.vacationEasterStroke ?? colors.vacationEaster;
      default: return colors.vacationDefaultStroke ?? colors.vacationDefault;
    }
  };

  // Layer 2: Colored overlay rectangles
  vacationGroup
    .selectAll('rect.vacation-overlay')
    .data(vacationBands)
    .enter()
    .append('rect')
    .attr('class', 'vacation-overlay')
    .attr('x', d => Math.max(0, x(d.period.start)))
    .attr('y', d => rowTop(d.row.key))
    .attr('width', (d) => {
      const startX = Math.max(0, x(d.period.start));
      const endX = Math.min(innerWidth, x(d.period.end));
      return Math.max(0, endX - startX);
    })
    .attr('height', d => rowHeightFor(d.row.key))
    .attr('fill', (d) => {
      switch (d.period.type) {
        case 'summer': return colors.vacationSummer;
        case 'winter': return colors.vacationWinter;
        case 'easter': return colors.vacationEaster;
        default: return colors.vacationDefault;
      }
    })
    .attr('pointer-events', 'none')
    .append('title')
    .text((d) => {
      const typeLabel = d.period.type === 'summer'
        ? 'Summer vacation'
        : d.period.type === 'winter'
          ? 'Winter vacation'
          : d.period.type === 'easter' ? 'Easter vacation' : 'Vacation';
      return `${typeLabel}: ${d.period.start.toLocaleDateString()} - ${d.period.end.toLocaleDateString()}`;
    });

  // Layer 3: Left/right border lines for each vacation period (once per period, spanning all rows)
  for (const period of vacationPeriods) {
    const startX = Math.max(0, x(period.start));
    const endX = Math.min(innerWidth, x(period.end));
    const strokeColor = getStrokeColor(period.type);

    // Calculate total height span for institution rows
    const firstRow = institutionRows[0];
    const lastRow = institutionRows[institutionRows.length - 1];
    if (!firstRow || !lastRow) continue;

    const topY = rowTop(firstRow.key);
    const bottomY = rowTop(lastRow.key) + rowHeightFor(lastRow.key);

    // Left border
    if (startX > 0) {
      vacationGroup
        .append('line')
        .attr('class', 'vacation-border-left')
        .attr('x1', startX)
        .attr('x2', startX)
        .attr('y1', topY)
        .attr('y2', bottomY)
        .attr('stroke', strokeColor)
        .attr('stroke-width', 1)
        .attr('pointer-events', 'none');
    }

    // Right border
    if (endX < innerWidth) {
      vacationGroup
        .append('line')
        .attr('class', 'vacation-border-right')
        .attr('x1', endX)
        .attr('x2', endX)
        .attr('y1', topY)
        .attr('y2', bottomY)
        .attr('stroke', strokeColor)
        .attr('stroke-width', 1)
        .attr('pointer-events', 'none');
    }
  }
}
