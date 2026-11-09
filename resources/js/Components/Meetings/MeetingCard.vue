<template>
  <Link
    :href
    prefetch
    :class="[
      'group flex h-full min-w-0 flex-col gap-3 border border-border bg-secondary/40 p-4 transition-colors',
      'hover:border-foreground/40 hover:bg-secondary/70',
      'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
    ]"
    data-slot="meeting-card"
  >
    <div class="flex items-start gap-3">
      <time
        :datetime="meeting.start_time"
        class="flex size-14 shrink-0 flex-col items-center justify-center border border-border bg-background text-center"
      >
        <span class="text-[11px] leading-none font-semibold uppercase text-brand">{{ month }}</span>
        <span class="mt-1 text-2xl leading-none font-semibold tabular-nums text-foreground">{{ day }}</span>
      </time>
      <div class="min-w-0 flex-1">
        <p class="line-clamp-2 font-bold text-foreground group-hover:text-brand">
          {{ heading }}
        </p>
        <p class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground">
          <span class="first-letter:uppercase">{{ when }}</span>
          <template v-if="upcoming">
            <span aria-hidden="true">·</span>
            <span class="font-semibold text-status-info" data-slot="meeting-card-upcoming">{{ $t('meetings.card.upcoming') }}</span>
          </template>
        </p>
      </div>
    </div>

    <!-- How it went, said as the meetings list says it; the documents close the card. -->
    <div class="mt-auto flex flex-col gap-2 border-t border-border/60 pt-3">
      <MeetingOutcome v-if="!upcoming" :outcome="meeting.outcome ?? {}" show-complete />
      <p v-else class="text-xs text-muted-foreground">
        {{ meeting.agenda_items_count
          ? $tChoice('meetings.card.agenda_items', meeting.agenda_items_count, { count: String(meeting.agenda_items_count) })
          : $t('meetings.card.no_agenda') }}
      </p>
      <p v-if="meeting.has_protocol || meeting.has_report" class="flex flex-wrap items-center gap-x-3 text-xs text-muted-foreground" data-slot="meeting-card-documents">
        <span v-if="meeting.has_protocol" class="inline-flex items-center gap-1">
          <FileCheck class="size-3.5" aria-hidden="true" />
          {{ $t('Protokolas') }}
        </span>
        <span v-if="meeting.has_report" class="inline-flex items-center gap-1">
          <ClipboardCheck class="size-3.5" aria-hidden="true" />
          {{ $t('Ataskaita') }}
        </span>
      </p>
    </div>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClipboardCheck, FileCheck } from '@lucide/vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { useDateFormatter } from '@/Composables/useDateFormatter';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import { MeetingType } from '@/Types/MeetingType';
import { parseDate } from '@/Utils/dateTime';

import MeetingOutcome from './MeetingOutcome.vue';
import { titleWithoutGeneratedDate } from './meetingTitle';

export interface MeetingCardData {
  id: string;
  title: string | null;
  start_time: string;
  type?: string | null;
  agenda_items_count?: number;
  vote_matches?: number;
  vote_mismatches?: number;
  incomplete_vote_data?: number;
  /** `Meeting::outcomeSummary()`, as the meetings index carries it. */
  outcome?: Partial<MeetingSearchResult>;
  has_protocol?: boolean;
  has_report?: boolean;
}

/** A meeting as a card: for a short row of recent meetings, where a list would be too thin. */
const props = defineProps<{
  meeting: MeetingCardData;
  href: string;
}>();

const { formatDateWith, formatMonthAbbr } = useDateFormatter();

const month = computed(() => formatMonthAbbr(props.meeting.start_time));
const day = computed(() => formatDateWith(props.meeting.start_time, { day: 'numeric' }));
const isEmail = computed(() => props.meeting.type === MeetingType.Email);

const when = computed(() => {
  const start = parseDate(props.meeting.start_time);
  const otherYear = start && start.getFullYear() !== new Date().getFullYear();
  const fields: Intl.DateTimeFormatOptions = {
    weekday: 'long',
    ...(otherYear ? { year: 'numeric' } : {}),
    ...(isEmail.value ? {} : { hour: '2-digit', minute: '2-digit' }),
  };

  return formatDateWith(props.meeting.start_time, fields);
});

const upcoming = computed(() => (parseDate(props.meeting.start_time)?.getTime() ?? 0) > Date.now());

// A title someone wrote says more than the date the plate already shows; a generated one becomes the meeting's kind.
const heading = computed(() => {
  const withoutDate = titleWithoutGeneratedDate(props.meeting.title);
  if (withoutDate && withoutDate === props.meeting.title) {
    return withoutDate;
  }

  if (isEmail.value) {
    return $t('Sprendimas el. paštu');
  }

  return props.meeting.type === MeetingType.Remote ? $t('Nuotolinis posėdis') : withoutDate ?? $t('meetings.card.fallback_title');
});

</script>
