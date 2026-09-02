/**
 * useActionWindow — global state for the guided action window ("Veiksmų langas").
 *
 * Provide/inject, following useCommandPalette.ts, so any page can open the window
 * without owning a dialog of its own.
 *
 * Navigation is a *stack* rather than a step number. That is what lets one screen
 * serve several flows (the institution picker is shared by the meeting and
 * check-in flows) and what makes "tap a review row to change it" a plain pop.
 *
 * @example
 * // AdminLayout.vue (provider):
 * createActionWindowProvider()
 *
 * // anywhere:
 * const { open } = useActionWindow()
 * open({ flow: 'meeting.create', institution })
 */

import { computed, inject, provide, reactive, readonly, ref, type InjectionKey, type Ref } from 'vue';

import { invalidateActionWindowData } from '@/Composables/useActionWindowData';
import { toLocalDateTime, type AgendaItemFormData, type MeetingFormData } from '@/Composables/useMeetingCreation';
import { isDateOnlyMeetingType } from '@/Types/MeetingType';

export type ScreenId
  = | 'persona'
    | 'persona.actions'
    | 'meeting.institution'
    | 'meeting.institution.search'
    | 'meeting.type'
    | 'meeting.when'
    | 'meeting.date'
    | 'meeting.time'
    | 'meeting.agenda'
    | 'meeting.review'
    | 'meeting.pick'
    | 'checkin.institution'
    | 'checkin.until'
    | 'checkin.review';

export interface ScreenFrame {
  id: ScreenId;
  params?: Record<string, unknown>;
}

/** What the window needs to know about the institution a flow is filed under. */
export interface ActionWindowInstitutionRef {
  id: string;
  name: string;
  /** Only VU SA's own bodies may be announced in the calendar; undefined until known. */
  isInternal?: boolean;
}

/** The calendar announcement a meeting is being created from, when there is one. */
export interface ActionWindowCalendarEventRef {
  id: number;
  title: string;
  date: string;
}

export interface ActionWindowDraft {
  institution?: ActionWindowInstitutionRef;
  /** Set only by the "create a meeting from this event" entry point. */
  calendarEvent?: ActionWindowCalendarEventRef;
  meeting: Partial<MeetingFormData>;
  agendaItems: AgendaItemFormData[];
  checkIn: { startDate?: string; endDate?: string; note: string };
}

export interface OpenOptions {
  /** Jump straight into a flow instead of the persona screen. */
  flow?: 'meeting.create' | 'check-in' | 'meeting.complete';
  institution?: ActionWindowInstitutionRef | null;
  suggestedAt?: Date | string | null;
  /**
   * Create the meeting from an existing announcement. The event's date becomes the
   * meeting's, so the flow never asks for one, and the two are linked on submit.
   */
  calendarEvent?: ActionWindowCalendarEventRef | null;
}

export interface ActionWindowContext {
  isOpen: Ref<boolean>;
  stack: ScreenFrame[];
  draft: ActionWindowDraft;
  current: Readonly<Ref<ScreenFrame>>;
  canGoBack: Readonly<Ref<boolean>>;
  /** Screens this run will never reach, so the progress dots don't count them. */
  skippedScreens: Readonly<Ref<ScreenId[]>>;
  /** The date came from elsewhere (an announcement), so no screen may ask for it. */
  isDateLocked: Readonly<Ref<boolean>>;
  open: (options?: OpenOptions) => void;
  close: () => void;
  goTo: (id: ScreenId, params?: Record<string, unknown>) => void;
  /**
   * Move on from the current screen. Normally pushes `next`, but a screen opened
   * from the review to change one answer returns straight there instead of walking
   * the rest of the flow again.
   */
  advance: (next: ScreenId) => void;
  /** Open `id` to change one answer, then come back to the current screen. */
  editFromHere: (id: ScreenId) => void;
  /** Replace the top frame — used when a screen advances without deepening history. */
  replace: (id: ScreenId, params?: Record<string, unknown>) => void;
  back: () => void;
  /** Pop back to an earlier screen; no-op when it is not on the stack. */
  backTo: (id: ScreenId) => void;
  reset: () => void;
  setInstitution: (institution: ActionWindowInstitutionRef) => void;
  updateMeeting: (data: Partial<MeetingFormData>) => void;
  setAgendaItems: (items: AgendaItemFormData[]) => void;
  updateCheckIn: (data: Partial<ActionWindowDraft['checkIn']>) => void;
}

const ACTION_WINDOW_INJECTION_KEY: InjectionKey<ActionWindowContext> = Symbol('action-window');

const emptyDraft = (): ActionWindowDraft => ({
  institution: undefined,
  calendarEvent: undefined,
  meeting: { start_time: '', type: undefined, description: '', announce_in_calendar: false },
  agendaItems: [],
  checkIn: { startDate: undefined, endDate: undefined, note: '' },
});

const ROOT_FRAME: ScreenFrame = { id: 'persona' };

/**
 * Where a flow starts, given what the caller already knows. Seeding an
 * institution skips its picker — that reproduces what NewMeetingDialog did when
 * it was handed one.
 */
function initialStack(options: OpenOptions | undefined, hasInstitution: boolean): ScreenFrame[] {
  switch (options?.flow) {
    case 'meeting.create':
      return hasInstitution ? [{ id: 'meeting.type' }] : [{ id: 'meeting.institution' }];
    case 'check-in':
      return hasInstitution ? [{ id: 'checkin.until' }] : [{ id: 'checkin.institution' }];
    case 'meeting.complete':
      return [{ id: 'meeting.pick' }];
    default:
      return [{ ...ROOT_FRAME }];
  }
}

export function createActionWindowProvider(): ActionWindowContext {
  const isOpen = ref(false);
  const stack = reactive<ScreenFrame[]>([{ ...ROOT_FRAME }]);
  const draft = reactive<ActionWindowDraft>(emptyDraft());

  const current = computed<ScreenFrame>(() => stack[stack.length - 1] ?? { ...ROOT_FRAME });
  const canGoBack = computed(() => stack.length > 1);
  const isDateLocked = computed(() => draft.calendarEvent !== undefined);
  const skippedScreens = computed<ScreenId[]>(() =>
    isDateLocked.value ? ['meeting.when', 'meeting.date', 'meeting.time'] : [],
  );

  const reset = () => {
    stack.splice(0, stack.length, { ...ROOT_FRAME });
    Object.assign(draft, emptyDraft());
  };

  const open = (options?: OpenOptions) => {
    reset();

    // The window's institution/meeting data is fetched once per cache lifetime (see
    // useActionWindowData), but meetings get completed on pages this window never
    // touches (ShowMeeting, agenda items, votes). Without this, a meeting fixed since
    // the window was last opened keeps showing as needing attention.
    invalidateActionWindowData();

    if (options?.institution) {
      draft.institution = options.institution;
      draft.meeting.institution_id = options.institution.id;
    }

    if (options?.calendarEvent) {
      draft.calendarEvent = options.calendarEvent;
      draft.meeting.calendar_id = options.calendarEvent.id;
      draft.meeting.start_time = toLocalDateTime(new Date(options.calendarEvent.date));
    }

    if (options?.suggestedAt) {
      const date = typeof options.suggestedAt === 'string' ? new Date(options.suggestedAt) : options.suggestedAt;
      if (!Number.isNaN(date.getTime())) {
        draft.meeting.start_time = toLocalDateTime(date);
      }
    }

    stack.splice(0, stack.length, ...initialStack(options, !!options?.institution));
    isOpen.value = true;
  };

  const close = () => {
    isOpen.value = false;
  };

  const goTo = (id: ScreenId, params?: Record<string, unknown>) => {
    stack.push({ id, params });
  };

  const replace = (id: ScreenId, params?: Record<string, unknown>) => {
    stack.splice(stack.length - 1, 1, { id, params });
  };

  const back = () => {
    if (stack.length > 1) {
      stack.pop();
    }
  };

  const backTo = (id: ScreenId) => {
    const index = stack.findIndex(frame => frame.id === id);
    if (index >= 0) {
      stack.splice(index + 1, stack.length - index - 1);
    }
  };

  const advance = (next: ScreenId) => {
    const returnTo = current.value.params?.returnTo as ScreenId | undefined;

    if (returnTo) {
      backTo(returnTo);
      return;
    }

    goTo(next);
  };

  const editFromHere = (id: ScreenId) => {
    goTo(id, { returnTo: current.value.id });
  };

  const setInstitution = (institution: ActionWindowInstitutionRef) => {
    draft.institution = institution;
    draft.meeting.institution_id = institution.id;
  };

  /**
   * An email meeting is a deadline rather than an appointment, and the app stores that
   * as a 23:59 marker. Normalising here rather than in each screen keeps the invariant
   * true however the type and the date were reached — including changing the type on
   * the review after an hour had already been picked.
   */
  const updateMeeting = (data: Partial<MeetingFormData>) => {
    Object.assign(draft.meeting, data);

    if (!isDateOnlyMeetingType(draft.meeting.type ?? null) || !draft.meeting.start_time) {
      return;
    }

    const deadline = new Date(draft.meeting.start_time);

    if (!Number.isNaN(deadline.getTime())) {
      deadline.setHours(23, 59, 59, 0);
      draft.meeting.start_time = toLocalDateTime(deadline);
    }
  };

  const setAgendaItems = (items: AgendaItemFormData[]) => {
    draft.agendaItems = items;
  };

  const updateCheckIn = (data: Partial<ActionWindowDraft['checkIn']>) => {
    Object.assign(draft.checkIn, data);
  };

  const context: ActionWindowContext = {
    isOpen,
    stack,
    draft,
    current: readonly(current) as Readonly<Ref<ScreenFrame>>,
    canGoBack: readonly(canGoBack) as Readonly<Ref<boolean>>,
    skippedScreens: readonly(skippedScreens) as Readonly<Ref<ScreenId[]>>,
    isDateLocked: readonly(isDateLocked) as Readonly<Ref<boolean>>,
    open,
    close,
    goTo,
    advance,
    editFromHere,
    replace,
    back,
    backTo,
    reset,
    setInstitution,
    updateMeeting,
    setAgendaItems,
    updateCheckIn,
  };

  provide(ACTION_WINDOW_INJECTION_KEY, context);

  return context;
}

/**
 * Consume the action window from anywhere. Falls back to a no-op when no
 * provider is mounted, so call sites (and their tests) never have to guard.
 */
export function useActionWindow(): ActionWindowContext {
  const context = inject(ACTION_WINDOW_INJECTION_KEY, null);

  if (context) {
    return context;
  }

  if (import.meta.env.DEV) {
    console.warn('useActionWindow: No provider found. Make sure AdminLayout uses createActionWindowProvider.');
  }

  const noop = () => {};
  return {
    isOpen: ref(false),
    stack: reactive<ScreenFrame[]>([{ ...ROOT_FRAME }]),
    draft: reactive<ActionWindowDraft>(emptyDraft()),
    current: computed(() => ({ ...ROOT_FRAME })),
    canGoBack: computed(() => false),
    skippedScreens: computed(() => []),
    isDateLocked: computed(() => false),
    open: noop,
    close: noop,
    goTo: noop,
    advance: noop,
    editFromHere: noop,
    replace: noop,
    back: noop,
    backTo: noop,
    reset: noop,
    setInstitution: noop,
    updateMeeting: noop,
    setAgendaItems: noop,
    updateCheckIn: noop,
  };
}
