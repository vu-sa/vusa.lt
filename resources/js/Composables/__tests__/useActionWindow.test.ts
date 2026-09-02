import { describe, it, expect, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';

import {
  createActionWindowProvider,
  useActionWindow,
  type ActionWindowContext,
  type OpenOptions,
} from '@/Composables/useActionWindow';
import { invalidateActionWindowData } from '@/Composables/useActionWindowData';

vi.mock('@/Composables/useActionWindowData', () => ({
  invalidateActionWindowData: vi.fn(),
}));

/**
 * The provider calls provide(), so it only runs inside a component setup.
 * Mounting a throwaway host is the cheapest way to get at the real context.
 */
const makeWindow = (): ActionWindowContext => {
  let context!: ActionWindowContext;

  mount(defineComponent({
    setup() {
      context = createActionWindowProvider();
      return () => h('div');
    },
  }));

  return context;
};

const openedOn = (options: OpenOptions) => {
  const window = makeWindow();
  window.open(options);
  return window;
};

describe('useActionWindow', () => {
  it('opens on the persona screen when nothing is seeded', () => {
    const window = makeWindow();
    window.open();

    expect(window.isOpen.value).toBe(true);
    expect(window.current.value.id).toBe('persona');
    expect(window.canGoBack.value).toBe(false);
  });

  describe('the screen stack', () => {
    it('pushes and pops', () => {
      const window = makeWindow();
      window.open();
      window.goTo('persona.actions', { persona: 'representative' });

      expect(window.current.value.id).toBe('persona.actions');
      expect(window.current.value.params).toEqual({ persona: 'representative' });
      expect(window.canGoBack.value).toBe(true);

      window.back();
      expect(window.current.value.id).toBe('persona');
    });

    it('never pops past the root', () => {
      const window = makeWindow();
      window.open();
      window.back();
      window.back();

      expect(window.current.value.id).toBe('persona');
      expect(window.stack).toHaveLength(1);
    });

    it('replaces the top frame without deepening history', () => {
      const window = makeWindow();
      window.open();
      window.replace('persona.actions', { persona: 'member' });

      expect(window.current.value.id).toBe('persona.actions');
      expect(window.stack).toHaveLength(1);
      expect(window.canGoBack.value).toBe(false);
    });

    it('backTo unwinds to an earlier screen', () => {
      const window = openedOn({ flow: 'meeting.create' });
      window.goTo('meeting.type');
      window.goTo('meeting.when');
      window.goTo('meeting.review');

      window.backTo('meeting.type');

      expect(window.current.value.id).toBe('meeting.type');
      expect(window.stack.map(frame => frame.id)).toEqual(['meeting.institution', 'meeting.type']);
    });

    it('backTo is a no-op for a screen the flow skipped', () => {
      const window = openedOn({ flow: 'meeting.create', institution: { id: '1', name: 'MIF SPK' } });
      window.goTo('meeting.review');

      window.backTo('meeting.institution');

      expect(window.current.value.id).toBe('meeting.review');
    });
  });

  describe('changing one answer from the review', () => {
    const atReview = () => {
      const window = openedOn({ flow: 'meeting.create' });
      window.goTo('meeting.type');
      window.goTo('meeting.when');
      window.goTo('meeting.agenda');
      window.goTo('meeting.review');
      return window;
    };

    it('returns to the review instead of walking the rest of the flow again', () => {
      const window = atReview();

      window.editFromHere('meeting.type');
      expect(window.current.value.id).toBe('meeting.type');

      // What the type screen calls when the user picks one.
      window.advance('meeting.when');

      expect(window.current.value.id).toBe('meeting.review');
    });

    it('leaves the flow intact, so the review can still offer every row', () => {
      const window = atReview();

      window.editFromHere('meeting.when');
      window.advance('meeting.agenda');

      expect(window.stack.map(frame => frame.id)).toEqual([
        'meeting.institution', 'meeting.type', 'meeting.when', 'meeting.agenda', 'meeting.review',
      ]);
    });

    it('backing out of an amendment also lands on the review', () => {
      const window = atReview();

      window.editFromHere('meeting.agenda');
      window.back();

      expect(window.current.value.id).toBe('meeting.review');
    });

    it('advance() pushes normally when the screen was not opened to amend', () => {
      const window = openedOn({ flow: 'meeting.create' });
      window.goTo('meeting.type');

      window.advance('meeting.when');

      expect(window.current.value.id).toBe('meeting.when');
      expect(window.stack).toHaveLength(3);
    });
  });

  describe('open()', () => {
    it('starts a meeting flow at the institution picker', () => {
      expect(openedOn({ flow: 'meeting.create' }).current.value.id).toBe('meeting.institution');
    });

    it('skips the institution picker when the caller already knows it', () => {
      const window = openedOn({
        flow: 'meeting.create',
        institution: { id: '42', name: 'MIF SPK', isInternal: true },
      });

      expect(window.current.value.id).toBe('meeting.type');
      expect(window.canGoBack.value).toBe(false);
      expect(window.draft.institution).toEqual({ id: '42', name: 'MIF SPK', isInternal: true });
      expect(window.draft.meeting.institution_id).toBe('42');
    });

    it('skips straight to the date for a seeded check-in', () => {
      const window = openedOn({ flow: 'check-in', institution: { id: '7', name: 'VU Senatas' } });

      expect(window.current.value.id).toBe('checkin.until');
    });

    it('seeds the suggested date as a wall clock, not a UTC instant', () => {
      const suggested = new Date('2026-09-15T16:00:00.000Z');
      const window = openedOn({ flow: 'meeting.create', suggestedAt: suggested });

      // The server reads start_time in the app timezone, so an ISO string with a Z
      // would land the meeting hours away from the hour the user picked.
      const pad = (value: number) => String(value).padStart(2, '0');
      expect(window.draft.meeting.start_time).toBe(
        `${suggested.getFullYear()}-${pad(suggested.getMonth() + 1)}-${pad(suggested.getDate())}`
        + `T${pad(suggested.getHours())}:${pad(suggested.getMinutes())}:00`,
      );
      expect(window.draft.meeting.start_time).not.toContain('Z');
    });

    it('ignores an unparseable suggested date rather than writing NaN', () => {
      const window = openedOn({ flow: 'meeting.create', suggestedAt: 'not a date' });

      expect(window.draft.meeting.start_time).toBe('');
    });

    /**
     * Started from an announcement: the event already fixed when the meeting happens,
     * so the flow must not ask again — and the two are linked on submit.
     */
    it('takes its date from a calendar event and locks it', () => {
      const window = openedOn({
        flow: 'meeting.create',
        calendarEvent: { id: 42, title: 'VU SA Parlamento posėdis', date: '2026-09-15T18:00:00.000Z' },
      });

      expect(window.draft.meeting.calendar_id).toBe(42);
      expect(window.draft.meeting.start_time).not.toBe('');
      expect(window.draft.meeting.start_time).not.toContain('Z');
      expect(window.isDateLocked.value).toBe(true);
      expect(window.skippedScreens.value).toContain('meeting.when');
      expect(window.skippedScreens.value).toContain('meeting.date');
      expect(window.skippedScreens.value).toContain('meeting.time');
    });

    it('asks for a date like any other run when no event seeded one', () => {
      const window = openedOn({ flow: 'meeting.create' });

      expect(window.isDateLocked.value).toBe(false);
      expect(window.skippedScreens.value).toEqual([]);
      expect(window.draft.meeting.calendar_id).toBeUndefined();
    });

    it('clears a previous draft so a second run does not inherit the first', () => {
      const window = openedOn({ flow: 'meeting.create', institution: { id: '1', name: 'MIF SPK' } });
      window.updateMeeting({ type: 'remote' });
      window.setAgendaItems([{ title: 'Klausimas', order: 1 }]);

      window.open();

      expect(window.current.value.id).toBe('persona');
      expect(window.draft.institution).toBeUndefined();
      expect(window.draft.calendarEvent).toBeUndefined();
      expect(window.draft.meeting.type).toBeUndefined();
      expect(window.draft.agendaItems).toEqual([]);
    });
  });

  it('close() leaves the draft alone so a reopen is a deliberate reset', () => {
    const window = openedOn({ flow: 'meeting.create', institution: { id: '1', name: 'MIF SPK' } });
    window.close();

    expect(window.isOpen.value).toBe(false);
    expect(window.draft.institution).toEqual({ id: '1', name: 'MIF SPK' });
  });

  it('open() drops the cached action-window data so a reopen re-reads the server', () => {
    vi.mocked(invalidateActionWindowData).mockClear();
    const window = makeWindow();

    window.open();

    expect(invalidateActionWindowData).toHaveBeenCalledTimes(1);
  });

  it('falls back to a no-op context when no provider is mounted', () => {
    let context!: ActionWindowContext;

    mount(defineComponent({
      setup() {
        context = useActionWindow();
        return () => h('div');
      },
    }));

    expect(() => context.open({ flow: 'meeting.create' })).not.toThrow();
    expect(context.isOpen.value).toBe(false);
  });
});
