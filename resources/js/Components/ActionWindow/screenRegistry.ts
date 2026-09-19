/**
 * Screen id → component. Async so the window's screens never weigh on the
 * initial admin bundle: nothing here loads until someone opens the window.
 */

import { defineAsyncComponent, type AsyncComponentLoader, type Component } from 'vue';

import ScreenLoading from './ScreenLoading.vue';

import type { ScreenId } from '@/Composables/useActionWindow';
import { ModelEnum } from '@/Types/enums';

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
  'meeting.institution.search': screen(() => import('./screens/InstitutionSearchScreen.vue')),
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
 * The institution step is skipped when the caller already knows the institution, so it
 * counts only when the stack actually visited it. Every other skip is declared by the
 * window itself (`skippedScreens`) rather than inferred from history — a step the user
 * simply has not reached yet must still be counted.
 */
const MEETING_FLOW: ScreenId[][] = [
  ['meeting.institution', 'meeting.institution.search'],
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

export function flowProgress(
  current: ScreenId,
  visited: ScreenId[],
  skipped: ScreenId[] = [],
): FlowProgress | null {
  const flow = FLOWS.find(steps => steps.some(screens => screens.includes(current)));

  if (!flow) {
    return null;
  }

  const withoutInstitution = flow[0]!.some(id => visited.includes(id)) ? flow : flow.slice(1);
  const steps = withoutInstitution.filter(screens => !screens.every(id => skipped.includes(id)));
  const index = steps.findIndex(screens => screens.includes(current));

  return index < 0 ? null : { step: index + 1, total: steps.length };
}

/**
 * The action that owns a screen, so the header can keep saying which job you are in the
 * middle of rather than only how far along you are. The entity marks it with its own
 * icon and category colour, as it does everywhere else.
 */
export interface FlowIdentity {
  entity: ModelEnum;
  /** Lang key for the eyebrow. */
  label: string;
}

const FLOW_IDENTITIES: Array<{ screens: ScreenId[]; identity: FlowIdentity }> = [
  {
    screens: MEETING_FLOW.flat(),
    identity: { entity: ModelEnum.MEETING, label: 'action_window.flows.new_meeting' },
  },
  {
    screens: CHECK_IN_FLOW.flat(),
    identity: { entity: ModelEnum.MEETING, label: 'action_window.flows.no_meeting' },
  },
  {
    screens: ['meeting.pick'],
    identity: { entity: ModelEnum.MEETING, label: 'action_window.flows.complete_meeting' },
  },
];

export function flowIdentity(current: ScreenId): FlowIdentity | null {
  return FLOW_IDENTITIES.find(entry => entry.screens.includes(current))?.identity ?? null;
}
