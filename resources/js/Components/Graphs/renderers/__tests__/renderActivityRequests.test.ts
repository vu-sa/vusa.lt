import { describe, expect, it } from 'vitest';

import { buildActivityRequestTooltipHtml } from '../renderActivityRequests';
import { createGanttTooltip } from '../GanttTooltip';
import { getGanttColors } from '../../ganttColors';

describe('activity request tooltip', () => {
  it('renders formatted dates as text', () => {
    const date = new Date('2026-11-06T10:00:00Z');
    const value = '<strong>Date</strong> & time';
    const fmt = { format: () => value } as Intl.DateTimeFormat;
    const html = buildActivityRequestTooltipHtml({
      institution_id: 1, rowKey: '1', fromDate: date, lastDate: date, untilDate: date,
      sentAt: date, expiresAt: date, recipients_count: 1,
    }, fmt);
    const manager = createGanttTooltip(document.createElement('div'), getGanttColors(false));
    manager.show({ type: 'request', html, priority: 3 }, 0, 0);

    expect(manager.getElement().node()?.textContent).toContain(value);
    expect(manager.getElement().node()?.querySelector('strong')).toBeNull();
  });

  it('shows the inclusive last day, not the day after it that the bar is drawn to', () => {
    const fmt = new Intl.DateTimeFormat('lt-LT');
    const html = buildActivityRequestTooltipHtml({
      institution_id: 1, rowKey: '1', fromDate: new Date(2026, 8, 1), lastDate: new Date(2026, 8, 30), untilDate: new Date(2026, 9, 1),
      sentAt: new Date(2026, 8, 1), expiresAt: new Date(2026, 9, 1), recipients_count: 1,
    }, fmt);

    expect(html).toContain(`${fmt.format(new Date(2026, 8, 1))} → ${fmt.format(new Date(2026, 8, 30))}`);
  });
});
