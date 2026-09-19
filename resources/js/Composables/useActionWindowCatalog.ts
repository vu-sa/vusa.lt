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
  FileText,
  GraduationCap,
  Landmark,
  MessageSquareWarning,
  PencilLine,
  Settings2,
  UserCog,
  type LucideIcon,
} from 'lucide-vue-next';

import type { ScreenId } from '@/Composables/useActionWindow';

/** The subset of `auth.can` the catalogue reads. */
export interface ActionWindowPermissions {
  create: Record<string, boolean | undefined>;
  /** `viewAny` per model, for actions that open a page rather than create a record. */
  index: Record<string, boolean | undefined>;
}

export interface ActionWindowAction {
  key: string;
  title: string;
  /** Omitted when the title already says everything. */
  description?: string;
  icon: LucideIcon;
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
  actions: ActionWindowAction[];
}

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
      actions: [
        {
          key: 'new_meeting',
          title: $t('action_window.actions.new_meeting.title'),
          description: $t('action_window.actions.new_meeting.description'),
          icon: CalendarPlus,
          requiresPermission: can => !!can.create.meeting,
          target: { kind: 'screen', screen: 'meeting.institution' },
        },
        {
          key: 'no_meeting',
          title: $t('action_window.actions.no_meeting.title'),
          description: $t('action_window.actions.no_meeting.description'),
          icon: CalendarOff,
          requiresPermission: can => !!can.create.meeting,
          target: { kind: 'screen', screen: 'checkin.institution' },
        },
        {
          key: 'complete_meeting',
          title: $t('action_window.actions.complete_meeting.title'),
          description: $t('action_window.actions.complete_meeting.description'),
          icon: PencilLine,
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
      actions: [
        {
          key: 'new_reservation',
          title: $t('action_window.actions.new_reservation.title'),
          description: $t('action_window.actions.new_reservation.description'),
          icon: Building2,
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
      actions: [
        {
          key: 'new_news',
          title: $t('action_window.actions.new_news.title'),
          description: $t('action_window.actions.new_news.description'),
          icon: FileText,
          requiresPermission: can => !!can.create.news,
          target: { kind: 'route', route: 'news.create' },
        },
        {
          key: 'duty_update',
          title: $t('action_window.actions.duty_update.title'),
          description: $t('action_window.actions.duty_update.description'),
          icon: UserCog,
          requiresPermission: can => !!can.create.duty,
          target: { kind: 'route', route: 'duties.updateUsersWizard' },
        },
        {
          key: 'duty_periods',
          title: $t('action_window.actions.cadences.title'),
          description: $t('action_window.actions.cadences.description'),
          icon: CalendarRange,
          // Mirrors DutiableTimelineController::index, which authorizes viewAny(Duty) —
          // gating on `dutiables.read` instead would lock out the coordinators the page
          // exists for, and gating on manage-settings would offer it to nobody else.
          requiresPermission: can => !!can.index.duty,
          target: { kind: 'route', route: 'dutiables.timeline' },
        },
      ],
    },
  ];
}

/** Personas and actions the current user may actually use. */
export function useActionWindowCatalog() {
  const page = usePage();

  const catalogActions = computed(() => new Map((page.props.adminNavigation?.workspaces ?? [])
    .flatMap(workspace => workspace.createActions)
    .map(action => [action.key, action])));

  const personas = computed<ActionWindowPersona[]>(() =>
    buildPersonas()
      .map(persona => ({
        ...persona,
        actions: persona.actions.flatMap((action) => {
          const catalogAction = catalogActions.value.get(action.key);

          if (!catalogAction) {
            return [];
          }

          return [{
            ...action,
            title: $t(catalogAction.label),
            description: catalogAction.description ? $t(catalogAction.description) : undefined,
            target: catalogAction.target.kind === 'route'
              ? { kind: 'route' as const, route: catalogAction.target.routeName }
              : { kind: 'screen' as const, screen: catalogAction.target.screen as ScreenId },
          }];
        }),
      }))
      .filter(persona => persona.actions.length > 0),
  );

  const hasAnyAction = computed(() => personas.value.length > 0);

  const findPersona = (key: PersonaKey) => personas.value.find(persona => persona.key === key);

  return { personas, hasAnyAction, findPersona };
}
