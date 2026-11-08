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
      institution_id: 1, rowKey: '1', fromDate: date, untilDate: date,
      sentAt: date, expiresAt: date, recipients_count: 1,
    }, fmt);
    const manager = createGanttTooltip(document.createElement('div'), getGanttColors(false));
    manager.show({ type: 'request', html, priority: 3 }, 0, 0);

    expect(manager.getElement().node()?.textContent).toContain(value);
    expect(manager.getElement().node()?.querySelector('strong')).toBeNull();
  });
});
