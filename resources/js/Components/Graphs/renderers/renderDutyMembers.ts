/**
 * renderDutyMembers - Render duty member avatar markers on the Gantt chart
 *
 * Renders small avatar circles with initials or profile photos showing
 * when duty members started their terms at institutions. Uses unified tooltip system.
 */
import type * as d3 from 'd3';

import type { GanttColors } from '../ganttColors';
import { isDarkModeActive } from '../ganttColors';

import type { GanttTooltipManager } from './GanttTooltip';
import { buildMemberTooltipContent } from './GanttTooltip';

import {
  getActivityRingColor,
  getActivityLabel,
  isDutyCurrentlyActive,
  type ActivityLevel,
} from '@/Pages/Admin/Dashboard/Composables/useActivityStatus';
import type { RepresentativeActivityCategory } from '@/Pages/Admin/Dashboard/types';

interface ParsedDutyMember {
  institution_id: string | number;
  startDate: Date;
  endDate: Date | null;
  user: {
    id: string;
    name: string;
    profile_photo_path?: string | null;
    // Activity status (only available in tenant view)
    activityCategory?: 'today' | 'week' | 'month' | 'stale' | 'never';
    lastAction?: string | null;
  };
}

export interface DutyMemberRenderContext {
  /** Main group element */
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  /** SVG defs element for clipPaths */
  defs: d3.Selection<SVGDefsElement, unknown, null, undefined>;
  /** Container element for positioning */
  container: HTMLElement;
  /** Time scale */
  x: d3.ScaleTime<number, number>;
  /** Grouped duty members (by institution + start date) */
  groupedDutyMembers: Map<string, ParsedDutyMember[]>;
  /** Inner width for visibility check */
  innerWidth: number;
  /** Whether details are expanded (affects avatar size) */
  detailsExpanded: boolean;
  /** Color palette */
  colors: GanttColors;
  /** Get row top position */
  rowTop: (key: string | number) => number;
  /** Get row height */
  rowHeightFor: (key: string | number) => number;
  /** Unified tooltip manager */
  tooltipManager?: GanttTooltipManager;
  /** Show activity status rings around avatars (tenant view only) */
  showActivityStatus?: boolean;
}

/**
 * Render duty member avatar markers
 */
export function renderDutyMembers(ctx: DutyMemberRenderContext): void {
  const { g, defs, container, x, groupedDutyMembers, innerWidth, detailsExpanded, colors, rowTop, rowHeightFor, tooltipManager, showActivityStatus } = ctx;

  if (groupedDutyMembers.size === 0) return;

  const avatarSize = detailsExpanded ? 26 : 20;
  const avatarOffset = avatarSize / 2;
  const isDark = isDarkModeActive();

  // Flatten grouped members for rendering
  const memberGroups = Array.from(groupedDutyMembers.entries()).map(([key, members]) => {
    const [instId] = key.split(':');
    const firstMember = members[0];
    return {
      key,
      institution_id: instId ?? '',
      startDate: firstMember?.startDate ?? new Date(),
      members: members.slice(0, 3), // Show max 3 avatars
      overflow: members.length > 3 ? members.length - 3 : 0,
    };
  }).filter(g => g.institution_id && g.members.length > 0);

  const memberGroup = g.append('g').attr('class', 'duty-member-markers');

  for (const group of memberGroups) {
    const xPos = x(group.startDate);
    const currentRowHeight = rowHeightFor(group.institution_id);
    const yPos = rowTop(group.institution_id) + (currentRowHeight - avatarSize) / 2;

    // Skip if outside visible range
    if (xPos < 0 || xPos > innerWidth) continue;

    const groupG = memberGroup.append('g')
      .attr('transform', `translate(${xPos - avatarOffset}, ${yPos})`);

    // Render stacked avatars
    group.members.forEach((member, idx) => {
      const offsetX = idx * (avatarSize * 0.6);
      const memberG = groupG.append('g')
        .attr('transform', `translate(${offsetX}, 0)`)
        .style('cursor', 'pointer');

      // Determine stroke color - use activity status ring only for CURRENT duties
      // A duty is current if endDate >= today OR endDate is null (ongoing)
      const isCurrentDuty = isDutyCurrentlyActive(member.startDate, member.endDate);
      const shouldShowActivityRing = showActivityStatus && isCurrentDuty && member.user.activityCategory;
      const activityRingColor = shouldShowActivityRing
        ? getActivityRingColor(member.user.activityCategory as RepresentativeActivityCategory, isDark)
        : null;
      const strokeColor = activityRingColor ?? (isDark ? '#4b5563' : '#d1d5db');
      const strokeWidth = activityRingColor ? 2 : 1;

      // Circle background
      memberG.append('circle')
        .attr('cx', avatarSize / 2)
        .attr('cy', avatarSize / 2)
        .attr('r', avatarSize / 2)
        .attr('fill', isDark ? '#374151' : '#e5e7eb')
        .attr('stroke', strokeColor)
        .attr('stroke-width', strokeWidth);

      if (member.user.profile_photo_path) {
        // Add clipPath for circular image
        const clipId = `avatar-clip-${member.user.id}-${idx}-${Date.now()}`;
        defs.append('clipPath')
          .attr('id', clipId)
          .append('circle')
          .attr('cx', avatarSize / 2)
          .attr('cy', avatarSize / 2)
          .attr('r', avatarSize / 2 - 1);

        memberG.append('image')
          .attr('href', member.user.profile_photo_path)
          .attr('x', 0)
          .attr('y', 0)
          .attr('width', avatarSize)
          .attr('height', avatarSize)
          .attr('clip-path', `url(#${clipId})`)
          .attr('preserveAspectRatio', 'xMidYMid slice');
      }
      else {
        // Show initials
        const initials = (member.user.name ?? '')
          .split(' ')
          .map(n => n.charAt(0))
          .slice(0, 2)
          .join('')
          .toUpperCase();

        memberG.append('text')
          .attr('x', avatarSize / 2)
          .attr('y', avatarSize / 2)
          .attr('text-anchor', 'middle')
          .attr('dominant-baseline', 'central')
          .attr('fill', isDark ? '#d1d5db' : '#374151')
          .style('font-size', '8px')
          .style('font-weight', '500')
          .text(initials);
      }

      // Hover events for tooltip - use unified tooltip manager if available
      if (tooltipManager) {
        memberG
          .on('mouseenter', (event) => {
            const rect = container.getBoundingClientRect();
            // Pass activity info to tooltip builder only for current duties with activity data
            const activityLabel = shouldShowActivityRing
              ? getActivityLabel(member.user.activityCategory as RepresentativeActivityCategory)
              : undefined;
            const content = buildMemberTooltipContent(member, activityLabel);
            tooltipManager.show(content, event.clientX - rect.left, event.clientY - rect.top);
          })
          .on('mousemove', (event) => {
            const rect = container.getBoundingClientRect();
            tooltipManager.updatePosition(event.clientX - rect.left, event.clientY - rect.top);
          })
          .on('mouseleave', () => {
            tooltipManager.hide();
          });
      }
      else {
        // Fallback: add native title tooltip
        memberG.append('title').text(() => {
          const startStr = member.startDate.toLocaleDateString();
          const endStr = member.endDate ? member.endDate.toLocaleDateString() : 'Present';
          const activityLabel = shouldShowActivityRing
            ? getActivityLabel(member.user.activityCategory as RepresentativeActivityCategory)
            : '';
          return `${member.user.name}\n${startStr} → ${endStr}${activityLabel ? `\n${activityLabel}` : ''}`;
        });
      }
    });

    // Show +N indicator if there are more members
    if (group.overflow > 0) {
      const overflowX = group.members.length * (avatarSize * 0.6);
      groupG.append('circle')
        .attr('cx', overflowX + avatarSize / 2)
        .attr('cy', avatarSize / 2)
        .attr('r', avatarSize / 2)
        .attr('fill', isDark ? '#4b5563' : '#d1d5db')
        .attr('stroke', isDark ? '#6b7280' : '#9ca3af')
        .attr('stroke-width', 1);

      groupG.append('text')
        .attr('x', overflowX + avatarSize / 2)
        .attr('y', avatarSize / 2)
        .attr('text-anchor', 'middle')
        .attr('dominant-baseline', 'central')
        .attr('fill', isDark ? '#d1d5db' : '#374151')
        .style('font-size', '8px')
        .style('font-weight', '500')
        .text(`+${group.overflow}`);
    }
  }
}
