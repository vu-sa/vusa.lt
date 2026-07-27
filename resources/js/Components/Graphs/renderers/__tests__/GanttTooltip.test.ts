import { describe, expect, it } from 'vitest';
import { JSDOM } from 'jsdom';

import { createGanttTooltip } from '../GanttTooltip';
import { getGanttColors } from '../../ganttColors';

function makeManager() {
  const dom = new JSDOM('<!DOCTYPE html><div id="container"></div>');
  const container = dom.window.document.getElementById('container')!;
  return createGanttTooltip(container as unknown as HTMLElement, getGanttColors(false));
}

describe('GanttTooltipManager priority', () => {
  it('allows the ambient hover handler to downgrade from gap to date content', () => {
    const manager = makeManager();

    manager.show({ type: 'gap', html: 'check-in', priority: 2 }, 0, 0);
    expect(manager.getCurrentType()).toBe('gap');

    // Cursor moved off the check-in date range, back to plain date hover —
    // this must be allowed even though 'date' (1) is a lower priority than
    // the 'gap' (2) currently showing, since both come from the same source.
    manager.show({ type: 'date', html: 'plain date', priority: 1 }, 0, 0);
    expect(manager.getCurrentType()).toBe('date');
  });

  it('still blocks the ambient handler from overriding a higher-priority meeting tooltip', () => {
    const manager = makeManager();

    manager.show({ type: 'meeting', html: 'meeting', priority: 3 }, 0, 0);
    manager.show({ type: 'date', html: 'plain date', priority: 1 }, 0, 0);

    expect(manager.getCurrentType()).toBe('meeting');
  });

  it('still blocks the ambient handler from overriding a higher-priority member tooltip with gap content', () => {
    const manager = makeManager();

    manager.show({ type: 'member', html: 'member', priority: 3 }, 0, 0);
    manager.show({ type: 'gap', html: 'check-in', priority: 2 }, 0, 0);

    expect(manager.getCurrentType()).toBe('member');
  });

  it('allows a higher-priority meeting tooltip to override an ambient gap tooltip', () => {
    const manager = makeManager();

    manager.show({ type: 'gap', html: 'check-in', priority: 2 }, 0, 0);
    manager.show({ type: 'meeting', html: 'meeting', priority: 3 }, 0, 0);

    expect(manager.getCurrentType()).toBe('meeting');
  });

  it('hide() resets state so any subsequent content shows', () => {
    const manager = makeManager();

    manager.show({ type: 'meeting', html: 'meeting', priority: 3 }, 0, 0);
    manager.hide();
    manager.show({ type: 'date', html: 'plain date', priority: 1 }, 0, 0);

    expect(manager.getCurrentType()).toBe('date');
  });
});
