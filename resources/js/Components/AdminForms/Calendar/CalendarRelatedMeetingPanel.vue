<template>
  <FormPanel :title="$t('Susietas posėdis')" :icon="CalendarClock">
    <div class="flex flex-col gap-3 text-xs">
      <div class="flex items-start justify-between gap-2">
        <a
          :href="route('meetings.show', { meeting: meeting.id })"
          target="_blank"
          rel="noopener noreferrer"
          class="group inline-flex items-center gap-1 font-semibold text-foreground hover:text-brand hover:underline"
        >
          <span>{{ meeting.institution_name ?? $t('Posėdis') }}</span>
          <ArrowUpRight class="size-3.5 text-muted-foreground group-hover:text-brand" aria-hidden="true" />
        </a>
        <span v-if="meeting.trashed" class="border border-destructive/30 bg-destructive/10 px-1.5 py-0.5 text-xs font-semibold text-destructive">
          {{ $t('meetings.announce.form_alert_trashed') }}
        </span>
      </div>

      <p class="text-muted-foreground">
        {{ meeting.agenda_items_count }} {{ $t('darbotvarkės punktai') }}
      </p>

      <p class="border-t border-border pt-2 leading-relaxed text-muted-foreground">
        {{ isDraft ? $t('meetings.announce.form_alert_draft') : $t('meetings.announce.form_alert_published') }}
      </p>

      <p class="italic text-muted-foreground/80">
        {{ $t('meetings.announce.timing_locked') }}
      </p>
    </div>
  </FormPanel>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, CalendarClock } from '@lucide/vue';

import { FormPanel } from '@/Components/Patterns';

export interface CalendarRelatedMeeting {
  id: string;
  start_time: string;
  title: string;
  trashed: boolean;
  agenda_items_count: number;
  institution_name: string | null;
}

/** The meeting this event publicly announces; its date follows the meeting. */
defineProps<{
  meeting: CalendarRelatedMeeting;
  isDraft: boolean;
}>();
</script>
