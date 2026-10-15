import { trans as $t } from 'laravel-vue-i18n';

/** Mirrors MeetingCompletionService::missingActions(). */
export type MeetingMissingAction
  = | { type: 'agenda_missing' }
    | { type: 'agenda_item_type_missing'; agenda_item_id: string; title: string; position: number }
    | {
      type: 'agenda_item_vote_missing';
      agenda_item_id: string;
      title: string;
      position: number;
      missing_fields: Array<'decision' | 'student_vote' | 'student_benefit'>;
    };

export type AgendaItemMissingAction = Exclude<MeetingMissingAction, { type: 'agenda_missing' }>;

export const isAgendaItemAction = (action: MeetingMissingAction): action is AgendaItemMissingAction =>
  action.type !== 'agenda_missing';

const FIELD_KEYS = {
  decision: 'meetings.completion.field_decision',
  student_vote: 'meetings.completion.field_student_vote',
  student_benefit: 'meetings.completion.field_student_benefit',
} as const;

/** "Trūksta: sprendimo, studentų pozicijos" — what one item still needs, in a phrase. */
export function missingFieldsLabel(action: MeetingMissingAction): string {
  if (action.type === 'agenda_missing') {
    return '';
  }

  const fields = action.type === 'agenda_item_type_missing'
    ? [$t('meetings.completion.field_type')]
    : (action.missing_fields.length ? action.missing_fields : ['decision' as const]).map(field => $t(FIELD_KEYS[field]));

  return $t('meetings.completion.missing', { fields: fields.join(', ') });
}
