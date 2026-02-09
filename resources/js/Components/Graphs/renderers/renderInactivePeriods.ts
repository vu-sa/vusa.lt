/**
 * renderInactivePeriods - Render inactive period overlays
 *
 * Renders diagonal striped rectangles showing periods when an institution
 * has no active duty members. This includes:
 * - Before the first member started
 * - After the last member ended (if all members have end_date in the past)
 * - Any explicit inactive periods from props
 */
import type * as d3 from 'd3';

interface InactivePeriod {
  institution_id: string | number;
  fromDate: Date;
  untilDate: Date;
}

interface DutyMember {
  institution_id: string | number;
  startDate: Date;
  endDate: Date | null;
}

export interface InactivePeriodsRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  /** Time scale */
  x: d3.ScaleTime<number, number>;
  /** Inner width of SVG */
  innerWidth: number;
  /** Filtered inactive periods from props */
  inactivePeriods: InactivePeriod[];
  /** Filtered duty members for computing inactive periods */
  dutyMembers: DutyMember[];
  /** Minimum time for the timeline */
  minTime: Date;
  /** Maximum time for the timeline (for "after last member" periods) */
  maxTime?: Date;
  /** Get row top position by key */
  rowTop: (key: string | number) => number;
  /** Get row height by key */
  rowHeightFor: (key: string | number) => number;
  /** All visible institution IDs (to detect institutions with no members) */
  allInstitutionIds?: Array<string | number>;
}

/**
 * Render inactive period overlays with diagonal stripes
 */
export function renderInactivePeriods(ctx: InactivePeriodsRenderContext): void {
  const { g, x, innerWidth, inactivePeriods, dutyMembers, minTime, maxTime, rowTop, rowHeightFor, allInstitutionIds } = ctx;

  // Group duty members by institution
  const membersByInstitution = new Map<string, DutyMember[]>();
  for (const member of dutyMembers) {
    const instId = String(member.institution_id);
    const arr = membersByInstitution.get(instId) ?? [];
    arr.push(member);
    membersByInstitution.set(instId, arr);
  }

  const computedInactivePeriods: InactivePeriod[] = [];

  // For institutions with NO members at all, show inactive for entire timeline
  if (allInstitutionIds) {
    for (const instId of allInstitutionIds) {
      const instIdStr = String(instId);
      if (!membersByInstitution.has(instIdStr)) {
        // Institution has no duty members at all - inactive for entire visible period
        const periodEnd = maxTime ?? new Date(new Date().getTime() + 365 * 24 * 60 * 60 * 1000);
        computedInactivePeriods.push({
          institution_id: instIdStr,
          fromDate: minTime,
          untilDate: periodEnd,
        });
      }
    }
  }

  // For each institution with members, compute inactive periods
  for (const [instId, members] of membersByInstitution) {
    // Sort members by start date
    members.sort((a, b) => a.startDate.getTime() - b.startDate.getTime());

    // Find the earliest start date
    const firstStart = members[0]?.startDate;
    if (firstStart && firstStart > minTime) {
      // "Before first member" period
      computedInactivePeriods.push({
        institution_id: instId,
        fromDate: minTime,
        untilDate: firstStart,
      });
    }

    // Find if there's currently no active member (all have ended)
    // An active member is one with no endDate or endDate in the future
    const now = new Date();
    const hasActiveMembers = members.some(m => !m.endDate || m.endDate > now);

    if (!hasActiveMembers && members.length > 0) {
      // Find the latest end date among all members
      let latestEndDate: Date | null = null;
      for (const m of members) {
        if (m.endDate) {
          if (!latestEndDate || m.endDate > latestEndDate) {
            latestEndDate = m.endDate;
          }
        }
      }

      if (latestEndDate) {
        // "After last member" period - from latest end date to maxTime or future
        const periodEnd = maxTime ?? new Date(now.getTime() + 365 * 24 * 60 * 60 * 1000); // 1 year from now as fallback
        if (latestEndDate < periodEnd) {
          computedInactivePeriods.push({
            institution_id: instId,
            fromDate: latestEndDate,
            untilDate: periodEnd,
          });
        }
      }
    }
  }

  // Combine with the prop-based inactive periods
  const allInactivePeriods = [...inactivePeriods, ...computedInactivePeriods];

  if (allInactivePeriods.length === 0) return;

  g.append('g')
    .attr('class', 'inactive-periods')
    .selectAll('rect.inactive-period')
    .data(allInactivePeriods)
    .enter()
    .append('rect')
    .attr('class', 'inactive-period')
    .attr('x', d => Math.max(0, x(d.fromDate)))
    .attr('y', d => rowTop(String(d.institution_id)) + 2)
    .attr('width', (d) => {
      const startX = Math.max(0, x(d.fromDate));
      const endX = Math.min(innerWidth, x(d.untilDate));
      return Math.max(0, endX - startX);
    })
    .attr('height', d => rowHeightFor(String(d.institution_id)) - 4)
    .attr('fill', 'url(#inactiveStripes)')
    .attr('rx', 2)
    .attr('ry', 2)
    .attr('pointer-events', 'none')
    .append('title')
    .text(d => `No active members: ${d.fromDate.toLocaleDateString()} → ${d.untilDate.toLocaleDateString()}`);
}
