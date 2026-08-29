/**
 * useActionWindowCatalog — what the action window is allowed to offer.
 *
 * Every entry carries the permission that gates it, checked against the same
 * `auth.can` map the sidebar quick actions use. Actions the user cannot perform
 * are *hidden*, never shown disabled: a first-time user should not be reading a
 * menu of things that will reject them. A persona with no permitted actions
 * disappears, and when nothing at all is permitted the entry point itself does
 * not render.
 *
 * These are UX filters only. Every flow still submits to an existing authorized
 * route, so the server stays the authority.
 */

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Building2,
  CalendarOff,
  CalendarPlus,
  CalendarRange,
  GraduationCap,
  Landmark,
  MessageSquareWarning,
  PencilLine,
  Settings2,
  UserCog,
  type LucideIcon,
} from 'lucide-vue-next';

import { quickActionGradient } from '@/Composables/useQuickActions';
import type { ScreenId } from '@/Composables/useActionWindow';

/** The subset of `auth.can` the catalogue reads. */
export interface ActionWindowPermissions {
  create: Record<string, boolean | undefined>;
  manageSettings: boolean;
}

export interface ActionWindowAction {
  key: string;
  title: string;
  /** Omitted when the title already says everything. */
  description?: string;
  icon: LucideIcon;
  /** Icon-tile tint, shared with the sidebar quick actions where the key matches. */
  gradient: string;
  requiresPermission: (can: ActionWindowPermissions) => boolean;
  /** Either push a screen inside the window, or leave for a page. */
  target: { kind: 'screen'; screen: ScreenId } | { kind: 'route'; route: string };
}

export type PersonaKey = 'representative' | 'member' | 'coordinator';

export interface ActionWindowPersona {
  key: PersonaKey;
  title: string;
  description: string;
  icon: LucideIcon;
  gradient: string;
  actions: ActionWindowAction[];
}

/**
 * Tints for actions the sidebar never had, written in the same idiom as
 * QUICK_ACTION_META so the two sets read as one palette.
 */
const GRADIENTS = {
  representative: 'from-amber-500/15 to-orange-500/15 dark:from-amber-400/12 dark:to-orange-400/12',
  member: 'from-emerald-500/15 to-teal-500/15 dark:from-emerald-400/12 dark:to-teal-400/12',
  coordinator: 'from-violet-500/15 to-purple-500/15 dark:from-violet-400/12 dark:to-purple-400/12',
  no_meeting: 'from-amber-500/20 to-yellow-500/15 dark:from-amber-400/15 dark:to-yellow-400/12',
  complete_meeting: 'from-sky-500/15 to-indigo-500/15 dark:from-sky-400/12 dark:to-indigo-400/12',
  cadences: 'from-fuchsia-500/15 to-violet-500/15 dark:from-fuchsia-400/12 dark:to-violet-400/12',
} as const;

/** Falls back to the local palette for keys the sidebar never carried. */
const tint = (key: string, fallback: string): string => quickActionGradient(key) ?? fallback;

/**
 * Reporting a problem sits under both the representative and the member persona
 * on purpose — a representative raising a student problem is a first-class case,
 * not an administrative one.
 */
const reportProblem = (): ActionWindowAction => ({
  key: 'new_problem',
  title: $t('action_window.actions.new_problem.title'),
  description: $t('action_window.actions.new_problem.description'),
  icon: MessageSquareWarning,
  gradient: tint('new_problem', GRADIENTS.member),
  requiresPermission: can => !!can.create.problem,
  target: { kind: 'route', route: 'problems.create' },
});

export function buildPersonas(): ActionWindowPersona[] {
  return [
    {
      key: 'representative',
      title: $t('action_window.personas.representative.title'),
      description: $t('action_window.personas.representative.description'),
      icon: GraduationCap,
      gradient: GRADIENTS.representative,
      actions: [
        {
          key: 'new_meeting',
          title: $t('action_window.actions.new_meeting.title'),
          description: $t('action_window.actions.new_meeting.description'),
          icon: CalendarPlus,
          gradient: tint('new_meeting', GRADIENTS.representative),
          requiresPermission: can => !!can.create.meeting,
          target: { kind: 'screen', screen: 'meeting.institution' },
        },
        {
          key: 'no_meeting',
          title: $t('action_window.actions.no_meeting.title'),
          description: $t('action_window.actions.no_meeting.description'),
          icon: CalendarOff,
          gradient: GRADIENTS.no_meeting,
          requiresPermission: can => !!can.create.meeting,
          target: { kind: 'screen', screen: 'checkin.institution' },
        },
        {
          key: 'complete_meeting',
          title: $t('action_window.actions.complete_meeting.title'),
          description: $t('action_window.actions.complete_meeting.description'),
          icon: PencilLine,
          gradient: GRADIENTS.complete_meeting,
          requiresPermission: can => !!can.create.meeting,
          target: { kind: 'screen', screen: 'meeting.pick' },
        },
        reportProblem(),
      ],
    },
    {
      key: 'member',
      title: $t('action_window.personas.member.title'),
      description: $t('action_window.personas.member.description'),
      icon: Landmark,
      gradient: GRADIENTS.member,
      actions: [
        {
          key: 'new_reservation',
          title: $t('action_window.actions.new_reservation.title'),
          description: $t('action_window.actions.new_reservation.description'),
          icon: Building2,
          gradient: tint('new_reservation', GRADIENTS.member),
          requiresPermission: can => !!can.create.reservation,
          target: { kind: 'route', route: 'reservations.create' },
        },
        reportProblem(),
      ],
    },
    {
      key: 'coordinator',
      title: $t('action_window.personas.coordinator.title'),
      description: $t('action_window.personas.coordinator.description'),
      icon: Settings2,
      gradient: GRADIENTS.coordinator,
      actions: [
        {
          key: 'duty_update',
          title: $t('action_window.actions.duty_update.title'),
          description: $t('action_window.actions.duty_update.description'),
          icon: UserCog,
          gradient: tint('duty_update', GRADIENTS.coordinator),
          requiresPermission: can => !!can.create.duty,
          target: { kind: 'route', route: 'duties.updateUsersWizard' },
        },
        {
          key: 'cadences',
          title: $t('action_window.actions.cadences.title'),
          description: $t('action_window.actions.cadences.description'),
          icon: CalendarRange,
          gradient: GRADIENTS.cadences,
          // Mirrors CadenceController::index, which aborts 403 on canUserManageSettings().
          requiresPermission: can => can.manageSettings,
          target: { kind: 'route', route: 'settings.cadences.index' },
        },
      ],
    },
  ];
}

/** Personas and actions the current user may actually use. */
export function useActionWindowCatalog() {
  const page = usePage();

  const permissions = computed<ActionWindowPermissions>(() => {
    const can = (page.props.auth as {
      can?: { create?: Record<string, boolean | undefined>; manageSettings?: boolean };
    } | null)?.can;

    return { create: can?.create ?? {}, manageSettings: !!can?.manageSettings };
  });

  const personas = computed<ActionWindowPersona[]>(() =>
    buildPersonas()
      .map(persona => ({
        ...persona,
        actions: persona.actions.filter(action => action.requiresPermission(permissions.value)),
      }))
      .filter(persona => persona.actions.length > 0),
  );

  const hasAnyAction = computed(() => personas.value.length > 0);

  const findPersona = (key: PersonaKey) => personas.value.find(persona => persona.key === key);

  return { personas, hasAnyAction, findPersona };
}
