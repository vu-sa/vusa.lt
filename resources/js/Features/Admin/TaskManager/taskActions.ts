import { trans as $t } from 'laravel-vue-i18n';
import { Check, FileCheck, FilePlus2, Megaphone, RotateCcw, Trash2 } from 'lucide-vue-next';

import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import {
  getMeetingAgendaUrl,
  isAgendaCreationTask,
  isAgendaTask,
  isInstitutionTask,
  isMeetingTask,
  isPeriodicityGapTask,
  type TaskDisplayData,
} from '@/Composables/useTaskPresentation';

export type TaskActionKey = 'report' | 'agenda' | 'complete' | 'delete';

export interface TaskAction extends CollectionRowAction {
  key: TaskActionKey;
  /** The one action the preview pane fills with the brand colour. */
  primary?: boolean;
  /** One word, for the labelled first button of a row. */
  shortLabel: string;
}

/** Everything a person can do with a task, in the order the preview pane lists it. */
export function getTaskActions(task: TaskDisplayData): TaskAction[] {
  const actions: TaskAction[] = [];

  if (!task.completed_at && isPeriodicityGapTask(task) && isInstitutionTask(task)) {
    // The action window then asks whether a meeting took place or there was none.
    actions.push({
      key: 'report',
      label: $t('tasks.periodicity_gap.report_no_meeting'),
      shortLabel: $t('tasks.short_actions.report'),
      icon: Megaphone,
      primary: true,
    });
  }

  const agendaUrl = !task.completed_at && isAgendaTask(task) && isMeetingTask(task) ? getMeetingAgendaUrl(task) : null;
  if (agendaUrl) {
    const isCreation = isAgendaCreationTask(task);
    actions.push({
      key: 'agenda',
      label: isCreation ? $t('tasks.agenda.action_add_items') : $t('tasks.agenda.action_view_agenda'),
      shortLabel: isCreation ? $t('tasks.short_actions.agenda_add') : $t('tasks.short_actions.agenda_view'),
      icon: isCreation ? FilePlus2 : FileCheck,
      href: agendaUrl,
      primary: true,
    });
  }

  if (task.can_be_manually_completed !== false) {
    actions.push(task.completed_at
      ? { key: 'complete', label: $t('tasks.collection.reopen'), shortLabel: $t('tasks.short_actions.reopen'), icon: RotateCcw }
      : { key: 'complete', label: $t('tasks.collection.complete'), shortLabel: $t('tasks.short_actions.complete'), icon: Check, primary: actions.length === 0 });
  }

  if (task.can_delete) {
    actions.push({
      key: 'delete',
      label: task.can_be_manually_completed === false ? $t('tasks.delete_automatic') : $t('forms.delete'),
      shortLabel: $t('tasks.short_actions.delete'),
      icon: Trash2,
      destructive: true,
    });
  }

  return actions;
}

/**
 * A row already completes through its checkbox, so that button would only repeat it. The first
 * remaining action carries a one-word label so the row says what it does; the rest stay icons.
 */
export function getTaskRowActions(task: TaskDisplayData): TaskAction[] {
  return getTaskActions(task)
    .filter(action => action.key !== 'complete')
    .map((action, index) => (index === 0 ? { ...action, label: action.shortLabel, labelled: true } : action));
}
