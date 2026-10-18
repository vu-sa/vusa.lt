import { describe, expect, it, vi } from 'vitest';
import * as d3 from 'd3';

import { lightModeColors } from '../../ganttColors';
import { renderActivityRequests, type ParsedActivityRequest } from '../renderActivityRequests';
import type { GanttTooltipManager } from '../GanttTooltip';

function setup(request: Partial<ParsedActivityRequest> = {}) {
  const container = document.createElement('div');
  const svg = d3.select(container).append('svg');
  const g = svg.append('g') as unknown as d3.Selection<SVGGElement, unknown, null, undefined>;
  const x = d3.scaleTime().domain([new Date(2026, 0, 1), new Date(2026, 11, 31)]).range([0, 3640]);
  const tooltipManager = { show: vi.fn(), hide: vi.fn(), updatePosition: vi.fn() } as unknown as GanttTooltipManager;

  renderActivityRequests({
    g,
    container,
    x,
    requests: [{
      institution_id: 'a',
      rowKey: '::a',
      fromDate: new Date(2026, 6, 1),
      untilDate: new Date(2026, 9, 1),
      sentAt: new Date(2026, 9, 2),
      expiresAt: new Date(2026, 9, 16),
      recipients_count: 2,
      ...request,
    }],
    colors: lightModeColors,
    rowTop: () => 28,
    rowHeightFor: () => 28,
    visibleLeft: 0,
    visibleRight: 3640,
    tooltipManager,
  });

  return { container, x, tooltipManager };
}

describe('renderActivityRequests', () => {
  it('brackets the period asked about and puts the request mark in its middle', () => {
    const { container, x } = setup();

    const line = container.querySelector('.activity-request-line')!;
    expect(line.getAttribute('d')).toContain(`M${x(new Date(2026, 6, 1))}`);

    const chip = container.querySelector<SVGGElement>('.activity-request-chip')!;
    const middle = (x(new Date(2026, 6, 1)) + x(new Date(2026, 9, 1))) / 2;
    expect(chip.getAttribute('transform')).toContain(`translate(${middle - 8}`);
    expect(chip.getAttribute('aria-label')).toBeTruthy();
  });

  it('describes the request in the shared tooltip on hover and keyboard focus', () => {
    const { container, tooltipManager } = setup();
    const chip = container.querySelector<SVGGElement>('.activity-request-chip')!;

    chip.dispatchEvent(new MouseEvent('mouseenter'));
    chip.dispatchEvent(new FocusEvent('focus'));

    expect(tooltipManager.show).toHaveBeenCalledTimes(2);
    expect(vi.mocked(tooltipManager.show).mock.calls[0]![0]).toMatchObject({ type: 'request' });
  });
});
