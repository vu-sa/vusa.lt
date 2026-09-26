import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import {
  Building2,
  CalendarPlus,
  CalendarRange,
  FileText,
  MessageSquareWarning,
  UserCog,
} from 'lucide-vue-next';

export interface QuickActionMeta {
  key: string;
  title: string;
  description: string | null;
  icon: LucideIcon;
  gradient: string;
  target: { kind: 'route'; routeName: string } | { kind: 'screen'; screen: string };
  execute: (emit?: QuickActionEmits) => void;
}

export interface QuickActionEmits {
  (e: 'newMeeting'): void;
  (e: 'newNews'): void;
  (e: 'newReservation'): void;
}

const presentation: Record<string, { icon: LucideIcon; gradient: string }> = {
  new_problem: { icon: MessageSquareWarning, gradient: 'from-red-500/15 to-rose-500/15 hover:from-red-500/25 hover:to-rose-500/25 dark:from-red-400/10 dark:to-rose-400/10 dark:hover:from-red-400/20 dark:hover:to-rose-400/20' },
  new_meeting: { icon: CalendarPlus, gradient: 'from-amber-500/15 to-orange-500/15 hover:from-amber-500/25 hover:to-orange-500/25 dark:from-amber-400/10 dark:to-orange-400/10 dark:hover:from-amber-400/20 dark:hover:to-orange-400/20' },
  new_news: { icon: FileText, gradient: 'from-blue-500/15 to-cyan-500/15 hover:from-blue-500/25 hover:to-cyan-500/25 dark:from-blue-400/10 dark:to-cyan-400/10 dark:hover:from-blue-400/20 dark:hover:to-cyan-400/20' },
  new_reservation: { icon: Building2, gradient: 'from-emerald-500/15 to-teal-500/15 hover:from-emerald-500/25 hover:to-teal-500/25 dark:from-emerald-400/10 dark:to-teal-400/10 dark:hover:from-emerald-400/20 dark:hover:to-teal-400/20' },
  duty_update: { icon: UserCog, gradient: 'from-violet-500/15 to-purple-500/15 hover:from-violet-500/25 hover:to-purple-500/25 dark:from-violet-400/10 dark:to-purple-400/10 dark:hover:from-violet-400/20 dark:hover:to-purple-400/20' },
  duty_periods: { icon: CalendarRange, gradient: 'from-sky-500/15 to-indigo-500/15 hover:from-sky-500/25 hover:to-indigo-500/25 dark:from-sky-400/10 dark:to-indigo-400/10 dark:hover:from-sky-400/20 dark:hover:to-indigo-400/20' },
};

const fallback: { icon: LucideIcon; gradient: string } = {
  icon: FileText,
  gradient: 'from-muted to-muted hover:from-muted/80 hover:to-muted/80',
};

export function quickActionGradient(key: string): string | undefined {
  return presentation[key]?.gradient;
}

export function useAvailableQuickActions() {
  const page = usePage<PageProps>();

  const available = computed<QuickActionMeta[]>(() => (page.props.adminNavigation?.workspaces ?? [])
    .flatMap(workspace => workspace.createActions)
    .map((action) => {
      const visual = presentation[action.key] ?? fallback;

      return {
        key: action.key,
        title: action.label,
        description: action.description,
        icon: visual.icon,
        gradient: visual.gradient,
        target: action.target,
        execute: (emit?: QuickActionEmits) => {
          if (action.target.kind === 'route') {
            router.visit(route(action.target.routeName));
            return;
          }

          if (action.key === 'new_meeting') emit?.('newMeeting');
          if (action.key === 'new_news') emit?.('newNews');
          if (action.key === 'new_reservation') emit?.('newReservation');
        },
      };
    }));

  return { available };
}
