/**
 * renderActivityRequests - open "Ar vyko posėdis?" requests as a band over the period asked about,
 * centred in its row like a check-in, with a mail-question chip in the middle. "Papildyk posėdžių
 * įrašus" requests are drawn on their meetings instead (renderMeetings' `requestedMeetingIds`).
 */
import type * as d3 from 'd3';
import { trans as $t } from 'laravel-vue-i18n';

import type { GanttColors } from '../ganttColors';

import { TOOLTIP_PRIORITIES, type GanttTooltipManager } from './GanttTooltip';
import { MAIL_QUESTION_PATH } from './renderMeetings';

export interface ParsedActivityRequest {
  institution_id: string | number;
  rowKey: string;
  fromDate: Date;
  untilDate: Date;
  sentAt: Date | null;
  expiresAt: Date;
  recipients_count: number;
}

export interface ActivityRequestRenderContext {
  g: d3.Selection<SVGGElement, unknown, null, undefined>;
  container: HTMLElement;
  x: d3.ScaleTime<number, number>;
  requests: ParsedActivityRequest[];
  colors: GanttColors;
  rowTop: (key: string | number) => number;
  rowHeightFor: (key: string | number) => number;
  /** Pixel range on screen, so the chip of a span wider than the view stays visible */
  visibleLeft: number;
  visibleRight: number;
  tooltipManager?: GanttTooltipManager;
}

const CHIP_SIZE = 14;

export function buildActivityRequestTooltipHtml(request: ParsedActivityRequest, fmtDate: Intl.DateTimeFormat): string {
  const rows = [
    `<div class="font-medium text-[12px]">${$t('activity_requests.campaigns.activity_confirmation')}</div>`,
    `<div class="opacity-80">${fmtDate.format(request.fromDate)} → ${fmtDate.format(request.untilDate)}</div>`,
    `<div class="mt-1 opacity-70 text-[11px]">${$t('activity_requests.gantt.recipients', { count: String(request.recipients_count) })}</div>`,
  ];
  if (request.sentAt) {
    rows.push(`<div class="opacity-70 text-[11px]">${$t('activity_requests.gantt.sent', { date: fmtDate.format(request.sentAt) })}</div>`);
  }
  rows.push(`<div class="opacity-70 text-[11px]">${$t('activity_requests.gantt.expires', { date: fmtDate.format(request.expiresAt) })}</div>`);

  return rows.join('');
}

export function renderActivityRequests(ctx: ActivityRequestRenderContext): void {
  const { g, container, x, requests, colors, rowTop, rowHeightFor, visibleLeft, visibleRight, tooltipManager } = ctx;
  if (requests.length === 0) return;

  const fmtDate = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' });
  // Same geometry as a check-in bar, so it reads as belonging to its row.
  const bandHeight = (d: ParsedActivityRequest) => Math.min(rowHeightFor(d.rowKey) - 6, 18);
  const bandY = (d: ParsedActivityRequest) => rowTop(d.rowKey) + (rowHeightFor(d.rowKey) - bandHeight(d)) / 2;
  const centerY = (d: ParsedActivityRequest) => rowTop(d.rowKey) + rowHeightFor(d.rowKey) / 2;
  const x0 = (d: ParsedActivityRequest) => x(d.fromDate);
  const x1 = (d: ParsedActivityRequest) => Math.max(x0(d) + 2, x(d.untilDate));
  const chipX = (d: ParsedActivityRequest) => {
    const from = Math.max(x0(d), visibleLeft) + CHIP_SIZE / 2;
    const until = Math.min(x1(d), visibleRight) - CHIP_SIZE / 2;
    return until > from ? (from + until) / 2 : (x0(d) + x1(d)) / 2;
  };

  const group = g.append('g').attr('class', 'activity-requests');
  const spans = group.selectAll('g.activity-request').data(requests).enter().append('g').attr('class', 'activity-request');

  spans.append('rect')
    .attr('class', 'activity-request-band')
    .attr('x', x0)
    .attr('y', bandY)
    .attr('width', d => x1(d) - x0(d))
    .attr('height', bandHeight)
    .attr('fill', colors.activityRequestFill)
    .attr('stroke', colors.activityRequest)
    .attr('stroke-width', 1)
    .attr('stroke-dasharray', '4,2')
    .attr('pointer-events', 'none');

  const chips = spans.append('g')
    .attr('class', 'activity-request-chip')
    .attr('transform', d => `translate(${chipX(d) - CHIP_SIZE / 2}, ${centerY(d) - CHIP_SIZE / 2})`)
    .attr('tabindex', 0)
    .attr('role', 'img')
    .attr('aria-label', d => `${$t('activity_requests.campaigns.activity_confirmation')}: ${fmtDate.format(d.fromDate)} – ${fmtDate.format(d.untilDate)}`);

  chips.append('rect')
    .attr('width', CHIP_SIZE)
    .attr('height', CHIP_SIZE)
    .attr('fill', colors.activityRequestChipBg)
    .attr('stroke', colors.activityRequest);

  chips.append('path')
    .attr('d', MAIL_QUESTION_PATH)
    .attr('fill', 'none')
    .attr('stroke', colors.activityRequest)
    .attr('stroke-width', 2)
    .attr('stroke-linecap', 'round')
    .attr('stroke-linejoin', 'round')
    .attr('transform', `translate(1.5, 1.5) scale(${(CHIP_SIZE - 3) / 24})`);

  if (!tooltipManager) {
    chips.append('title').text(d => `${$t('activity_requests.campaigns.activity_confirmation')}: ${fmtDate.format(d.fromDate)} – ${fmtDate.format(d.untilDate)}`);
    return;
  }

  const show = (event: MouseEvent | FocusEvent, d: ParsedActivityRequest) => {
    const rect = container.getBoundingClientRect();
    const target = (event.currentTarget as Element).getBoundingClientRect();
    const pointer = event instanceof MouseEvent ? event : { clientX: target.right, clientY: target.bottom };
    tooltipManager.show(
      { type: 'request', html: buildActivityRequestTooltipHtml(d, fmtDate), priority: TOOLTIP_PRIORITIES.request },
      pointer.clientX - rect.left,
      pointer.clientY - rect.top,
    );
  };

  chips
    .on('mouseenter', show)
    .on('focus', show)
    .on('mousemove', (event: MouseEvent) => {
      const rect = container.getBoundingClientRect();
      tooltipManager.updatePosition(event.clientX - rect.left, event.clientY - rect.top);
    })
    .on('mouseleave', () => tooltipManager.hide())
    .on('blur', () => tooltipManager.hide());
}
