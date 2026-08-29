import { describe, expect, it } from 'vitest';
import { JSDOM } from 'jsdom';

import { buildMeetingTooltipContent, createGanttTooltip } from '../GanttTooltip';
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

/**
 * A meeting announced in the public calendar is a fact the chart could not previously show,
 * and a drafted announcement is invisible to everyone but the admins — so the two states
 * must read differently, matching ShowMeeting.vue's amber/green split.
 */
describe('buildMeetingTooltipContent calendar indicator', () => {
  const fmt = new Intl.DateTimeFormat('lt-LT');

  function build(overrides: Record<string, unknown>) {
    return buildMeetingTooltipContent(
      {
        id: 'm1',
        date: new Date('2026-03-04T10:00:00Z'),
        institution_id: 'i1',
        has_protocol: true,
        has_report: true,
        ...overrides,
      },
      () => 'VU Senatas',
      fmt,
    ).html;
  }

  it('marks a published announcement in green', () => {
    const html = build({ has_calendar_event: true, calendar_event_is_draft: false });

    expect(html).toContain('meetings.announce.published_hint');
    expect(html).toContain('text-green-600');
  });

  it('marks a drafted announcement in amber', () => {
    const html = build({ has_calendar_event: true, calendar_event_is_draft: true });

    expect(html).toContain('meetings.announce.draft_hint');
    expect(html).toContain('text-amber-500');
  });

  it('says nothing at all when the meeting was never announced', () => {
    const html = build({ has_calendar_event: false });

    expect(html).not.toContain('meetings.announce');
  });
});
