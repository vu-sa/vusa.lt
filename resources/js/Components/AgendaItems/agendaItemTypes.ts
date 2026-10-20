import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarClock, Coffee, Info, Vote } from '@lucide/vue';
import type { Component } from 'vue';

import { createVote, type AgendaItemFormData } from '@/Composables/useAgendaItemAutosave';

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
  { value: 'informational', label: $t('Informacinis'), description: $t('meetings.item.types.informational'), icon: Info },
  { value: 'deferred', label: $t('Atidėtas'), description: $t('meetings.item.types.deferred'), icon: CalendarClock },
  // A pause is a real agenda entry — excluding it forced editors to mistype it as something else.
  { value: 'break', label: $t('Pertrauka'), icon: Coffee },
];

/** Choosing "Balsavimas" opens the first vote straight away, so the outcome is one more tap. */
export function applyAgendaItemType(form: InertiaForm<AgendaItemFormData>, value: AgendaItemType): void {
  form.type = value;

  if (value === 'voting' && form.votes.length === 0) {
    form.votes.push(createVote());
  }
}
