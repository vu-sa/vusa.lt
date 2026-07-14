/**
 * useQuickActions — Permission-aware quick-action metadata
 *
 * Single source of truth for what quick actions exist, what they do,
 * what permissions they require, and what their labels/icons are.
 *
 * Consumed by:
 *  - NavQuickActions.vue (renders the sidebar items)
 *  - SidebarCustomizeDialog.vue (renders the toggles)
 *  - AdminCommandPalette.vue (renders the palette actions)
 */

import { computed, type Component } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import type { LucideIcon } from 'lucide-vue-next';
import {
  CalendarPlus,
  FileText,
  Building2,
  MessageSquareWarning,
  UserCog,
} from 'lucide-vue-next';

export interface QuickActionMeta {
  key: string;
  title: string;
  icon: LucideIcon;
  /** Permission check — receives auth.can.create */
  requiresPermission: (canCreate: Record<string, boolean | undefined>) => boolean;
  /** Execute the action */
  execute: (emit?: QuickActionEmits) => void;
  /** Tailwind gradient classes */
  gradient: string;
}

export interface QuickActionEmits {
  newMeeting: () => void;
  newNews: () => void;
  newReservation: () => void;
}

/** Static metadata for every quick action. */
export const QUICK_ACTION_META: QuickActionMeta[] = [
  {
    key: 'new_problem',
    title: $t('Nauja problema'),
    icon: MessageSquareWarning,
    requiresPermission: can => !!can.problem,
    execute: () => router.visit(route('problems.create')),
    gradient: 'from-red-500/15 to-rose-500/15 hover:from-red-500/25 hover:to-rose-500/25 dark:from-red-400/10 dark:to-rose-400/10 dark:hover:from-red-400/20 dark:hover:to-rose-400/20',
  },
  {
    key: 'new_meeting',
    title: $t('Naujas susitikimas'),
    icon: CalendarPlus,
    requiresPermission: can => !!can.meeting,
    execute: emit => emit?.newMeeting(),
    gradient: 'from-amber-500/15 to-orange-500/15 hover:from-amber-500/25 hover:to-orange-500/25 dark:from-amber-400/10 dark:to-orange-400/10 dark:hover:from-amber-400/20 dark:hover:to-orange-400/20',
  },
  {
    key: 'new_news',
    title: $t('Nauja naujiena'),
    icon: FileText,
    requiresPermission: can => !!can.news,
    execute: emit => emit?.newNews(),
    gradient: 'from-blue-500/15 to-cyan-500/15 hover:from-blue-500/25 hover:to-cyan-500/25 dark:from-blue-400/10 dark:to-cyan-400/10 dark:hover:from-blue-400/20 dark:hover:to-cyan-400/20',
  },
  {
    key: 'new_reservation',
    title: $t('Nauja rezervacija'),
    icon: Building2,
    requiresPermission: can => !!can.reservation,
    execute: emit => emit?.newReservation(),
    gradient: 'from-emerald-500/15 to-teal-500/15 hover:from-emerald-500/25 hover:to-teal-500/25 dark:from-emerald-400/10 dark:to-teal-400/10 dark:hover:from-emerald-400/20 dark:hover:to-teal-400/20',
  },
  {
    key: 'duty_update',
    title: $t('Pareigybių atnaujinimas'),
    icon: UserCog,
    requiresPermission: can => !!can.duty,
    execute: () => router.visit(route('duties.updateUsersWizard')),
    gradient: 'from-violet-500/15 to-purple-500/15 hover:from-violet-500/25 hover:to-purple-500/25 dark:from-violet-400/10 dark:to-purple-400/10 dark:hover:from-violet-400/20 dark:hover:to-purple-400/20',
  },
];

/** Returns the quick actions available to the current user. */
export function useAvailableQuickActions() {
  const page = usePage();

  const available = computed<QuickActionMeta[]>(() => {
    const canCreate = (page.props.auth as { can?: { create?: Record<string, boolean | undefined> } })?.can?.create || {};
    return QUICK_ACTION_META.filter(meta => meta.requiresPermission(canCreate));
  });

  return { available };
}
