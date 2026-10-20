import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarClock, Coffee, ListX, MessagesSquare, Vote } from '@lucide/vue';
import type { Component } from 'vue';

import { createVote, type AgendaItemFormData } from '@/Composables/useAgendaItemAutosave';
import { isEmailMeeting } from '@/Utils/MeetingDisplay';

export type AgendaItemType = NonNullable<AgendaItemFormData['type']>;

export interface AgendaItemTypeOption {
  value: AgendaItemType;
  label: string;
  /** Omitted where it would only restate the label. */
  description?: string;
  icon: Component;
}

export const agendaItemTypeOptions = (): AgendaItemTypeOption[] => [
  { value: 'voting', label: $t('Balsavimas'), description: $t('meetings.item.types.voting'), icon: Vote },
  { value: 'informational', label: $t('Aptarimas'), description: $t('meetings.item.types.informational'), icon: MessagesSquare },
  { value: 'deferred', label: $t('Atidėtas'), description: $t('meetings.item.types.deferred'), icon: CalendarClock },
  { value: 'withdrawn', label: $t('Išbrauktas'), description: $t('meetings.item.types.withdrawn'), icon: ListX },
  // A pause is a real agenda entry — excluding it forced editors to mistype it as something else.
  { value: 'break', label: $t('Pertrauka'), icon: Coffee },
];

export interface AgendaItemTypeMeeting {
  start_time?: string | Date | null;
  type?: string | { value?: string | null } | null;
}

/** The dialog's question follows the meeting: email or in person, held already or still ahead. */
export function agendaItemTypeQuestion(meeting?: AgendaItemTypeMeeting | null): string {
  const channel = meeting && isEmailMeeting(meeting) ? 'email' : 'meeting';
  const startsAt = meeting?.start_time ? new Date(meeting.start_time).getTime() : Number.NaN;
  const timing = Number.isNaN(startsAt) ? 'undated' : startsAt <= Date.now() ? 'past' : 'upcoming';

  return $t(`meetings.item.type_question.${channel}_${timing}`);
}

/** Choosing "Balsavimas" opens the first vote straight away, so the outcome is one more tap. */
export function applyAgendaItemType(form: InertiaForm<AgendaItemFormData>, value: AgendaItemType): void {
  form.type = value;

  if (value === 'voting' && form.votes.length === 0) {
    form.votes.push(createVote());
  }
}
