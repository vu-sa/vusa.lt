import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * The pending-task count the shell shows as a badge (O12: tasks are what needs you, so they
 * count in the navigation; notifications live in the bell). Overdue tasks turn it `danger`,
 * the rest are `attention` — the two roles the colour rules allow in navigation counts.
 */
export function useTaskBadge() {
  const page = usePage<PageProps>();

  return computed(() => {
    const user = page.props.auth?.user;
    const pending = user?.tasks_count ?? 0;
    const overdue = user?.overdue_tasks_count ?? 0;

    if (pending <= 0) {
      return null;
    }

    return {
      count: pending,
      role: overdue > 0 ? 'danger' as const : 'attention' as const,
      label: overdue > 0
        ? $t('shell.badges.tasks_overdue', { count: String(pending), overdue: String(overdue) })
        : $t('shell.badges.tasks_pending', { count: String(pending) }),
    };
  });
}
