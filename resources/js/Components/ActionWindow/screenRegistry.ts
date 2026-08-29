/**
 * Screen id → component. Async so the window's screens never weigh on the
 * initial admin bundle: nothing here loads until someone opens the window.
 */

import { defineAsyncComponent, type AsyncComponentLoader, type Component } from 'vue';
import { CalendarOff, CalendarPlus, PencilLine } from 'lucide-vue-next';

import ScreenLoading from './ScreenLoading.vue';

import type { ScreenId } from '@/Composables/useActionWindow';

/**
 * `delay` keeps the spinner off screen for chunks that arrive quickly, which is
 * every screen after the first — without it, moving between steps flashes a
 * loader on every tap.
 */
const screen = (loader: AsyncComponentLoader): Component =>
  defineAsyncComponent({ loader, loadingComponent: ScreenLoading, delay: 150 });

export const ACTION_WINDOW_SCREENS: Record<ScreenId, Component> = {
  'persona': screen(() => import('./screens/PersonaScreen.vue')),
  'persona.actions': screen(() => import('./screens/PersonaActionsScreen.vue')),
  'meeting.institution': screen(() => import('./screens/InstitutionPickerScreen.vue')),
  'meeting.type': screen(() => import('./screens/MeetingTypeScreen.vue')),
  'meeting.when': screen(() => import('./screens/MeetingWhenScreen.vue')),
  'meeting.date': screen(() => import('./screens/MeetingDateScreen.vue')),
  'meeting.time': screen(() => import('./screens/MeetingTimeScreen.vue')),
  'meeting.agenda': screen(() => import('./screens/MeetingAgendaScreen.vue')),
  'meeting.review': screen(() => import('./screens/MeetingReviewScreen.vue')),
  'meeting.pick': screen(() => import('./screens/MeetingPickerScreen.vue')),
  'checkin.institution': screen(() => import('./screens/InstitutionPickerScreen.vue')),
  'checkin.until': screen(() => import('./screens/CheckInUntilScreen.vue')),
  'checkin.review': screen(() => import('./screens/CheckInReviewScreen.vue')),
};

/**
 * Ordered *steps* per flow. A step can be reached by more than one screen — picking a
 * custom date splits "when" across a calendar and a clock, but the user is still on the
 * same step and the dots must not jump.
 *
 * The institution step is the only one ever skipped (when the caller already knows the
 * institution), so it counts only when the stack actually visited it.
 */
const MEETING_FLOW: ScreenId[][] = [
  ['meeting.institution'],
  ['meeting.type'],
  ['meeting.when', 'meeting.date', 'meeting.time'],
  ['meeting.agenda'],
  ['meeting.review'],
];

const CHECK_IN_FLOW: ScreenId[][] = [
  ['checkin.institution'],
  ['checkin.until'],
  ['checkin.review'],
];

const FLOWS: ScreenId[][][] = [MEETING_FLOW, CHECK_IN_FLOW];

export interface FlowProgress {
  step: number;
  total: number;
}

export function flowProgress(current: ScreenId, visited: ScreenId[]): FlowProgress | null {
  const flow = FLOWS.find(steps => steps.some(screens => screens.includes(current)));

  if (!flow) {
    return null;
  }

  const steps = visited.includes(flow[0]![0]!) ? flow : flow.slice(1);
  const index = steps.findIndex(screens => screens.includes(current));

  return index < 0 ? null : { step: index + 1, total: steps.length };
}

/**
 * The action that owns a screen, so the header can keep showing which job you are in
 * the middle of rather than only how far along you are.
 */
export interface FlowIdentity {
  icon: Component;
  gradient: string;
}

const MEETING_IDENTITY: FlowIdentity = {
  icon: CalendarPlus,
  gradient: 'from-amber-500/15 to-orange-500/15 dark:from-amber-400/12 dark:to-orange-400/12',
};

const FLOW_IDENTITIES: Array<{ screens: ScreenId[]; identity: FlowIdentity }> = [
  { screens: MEETING_FLOW.flat(), identity: MEETING_IDENTITY },
  {
    screens: CHECK_IN_FLOW.flat(),
    identity: {
      icon: CalendarOff,
      gradient: 'from-amber-500/20 to-yellow-500/15 dark:from-amber-400/15 dark:to-yellow-400/12',
    },
  },
  {
    screens: ['meeting.pick'],
    identity: {
      icon: PencilLine,
      gradient: 'from-sky-500/15 to-indigo-500/15 dark:from-sky-400/12 dark:to-indigo-400/12',
    },
  },
];

export function flowIdentity(current: ScreenId): FlowIdentity | null {
  return FLOW_IDENTITIES.find(entry => entry.screens.includes(current))?.identity ?? null;
}
