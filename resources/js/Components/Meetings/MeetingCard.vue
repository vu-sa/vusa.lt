<template>
  <Link
    :href
    prefetch
    :class="[
      'group flex h-full min-w-0 flex-col border border-border bg-card p-4 transition-colors hover:border-foreground/40',
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
        <p class="flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground">
          <span class="first-letter:uppercase">{{ when }}</span>
          <template v-if="upcoming">
            <span aria-hidden="true">·</span>
            <span class="font-semibold text-status-info" data-slot="meeting-card-upcoming">{{ $t('meetings.card.upcoming') }}</span>
          </template>
        </p>
        <p class="mt-1 line-clamp-2 font-bold text-foreground group-hover:text-brand">
          {{ heading }}
        </p>
      </div>
    </div>

    <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-4 text-xs text-muted-foreground">
      <span v-if="meeting.agenda_items_count">
        {{ $tChoice('meetings.card.agenda_items', meeting.agenda_items_count, { count: String(meeting.agenda_items_count) }) }}
      </span>
      <span v-else>{{ $t('meetings.card.no_agenda') }}</span>

      <span v-if="voteTotal > 0" class="inline-flex items-center gap-2" data-slot="meeting-card-votes">
        <span v-if="meeting.vote_matches" class="inline-flex items-center gap-0.5 text-status-success" :title="$t('Studentų pozicija priimta')">
          <Check class="size-3.5" aria-hidden="true" />
          <span class="sr-only">{{ $t('Studentų pozicija priimta') }}:</span>{{ meeting.vote_matches }}
        </span>
        <span v-if="meeting.vote_mismatches" class="inline-flex items-center gap-0.5 text-status-danger" :title="$t('Studentų pozicija nepriimta')">
          <X class="size-3.5" aria-hidden="true" />
          <span class="sr-only">{{ $t('Studentų pozicija nepriimta') }}:</span>{{ meeting.vote_mismatches }}
        </span>
        <span v-if="meeting.incomplete_vote_data" class="inline-flex items-center gap-0.5" :title="$t('meetings.card.incomplete_votes')">
          <Minus class="size-3.5" aria-hidden="true" />
          <span class="sr-only">{{ $t('meetings.card.incomplete_votes') }}:</span>{{ meeting.incomplete_vote_data }}
        </span>
      </span>

      <span v-if="meeting.has_protocol" class="inline-flex items-center gap-1">
        <FileCheck class="size-3.5" aria-hidden="true" />
        {{ $t('Protokolas') }}
      </span>
      <span v-if="meeting.has_report" class="inline-flex items-center gap-1">
        <ClipboardCheck class="size-3.5" aria-hidden="true" />
        {{ $t('Ataskaita') }}
      </span>
    </div>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, ClipboardCheck, FileCheck, Minus, X } from '@lucide/vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { useDateFormatter } from '@/Composables/useDateFormatter';
import { MeetingType } from '@/Types/MeetingType';
import { parseDate } from '@/Utils/dateTime';

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

const heading = computed(() => {
  if (isEmail.value && !props.meeting.title) {
    return $t('Sprendimas el. paštu');
  }

  return titleWithoutGeneratedDate(props.meeting.title) ?? $t('meetings.card.fallback_title');
});

const voteTotal = computed(() => (props.meeting.vote_matches ?? 0) + (props.meeting.vote_mismatches ?? 0) + (props.meeting.incomplete_vote_data ?? 0));
</script>
