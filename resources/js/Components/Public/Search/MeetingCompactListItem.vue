<template>
  <!-- Bleeds past the list's own edge on hover (-mx/px, equal and opposite) instead of boxing
       the row — the house idiom for full-width list rows, see .ai/rules/public.md. The stretched
       Link (not the li itself) carries the hover fill, since it is also what the whole row
       navigates through. -->
  <li class="group relative -mx-3 px-3 sm:-mx-4 sm:px-4">
    <Link
      :href="getMeetingUrl()"
      class="absolute inset-0 z-0 transition-colors hover:bg-secondary/50"
    >
      <span class="sr-only">{{ meeting.title || formatCompactDate() }}</span>
    </Link>

    <!-- pointer-events-none lets clicks/hover on empty space fall through to the stretched
         Link above; only the genuinely interactive pieces (the institution link, the outcome
         tooltips) opt back in with pointer-events-auto — not their containers, or the
         whitespace around them would swallow the row's own hover and click. -->
    <div class="relative z-10 flex items-center gap-3 py-4 pointer-events-none sm:gap-4 sm:py-5">
      <DatePlate :date="meetingDate" />

      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
          <span
            v-if="meeting.vote_alignment_status"
            class="size-2 shrink-0 rounded-full"
            :class="alignmentDotClass"
            :title="alignmentDotTitle"
          />
          <Link
            v-if="institutionName && meeting.institution_id"
            :href="getInstitutionUrl()"
            class="pointer-events-auto truncate font-bold text-foreground transition-colors hover:text-brand hover:underline"
          >
            {{ institutionName }}
          </Link>
          <span v-else-if="institutionName" class="truncate font-bold text-foreground">
            {{ institutionName }}
          </span>
        </div>
        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
          <time>{{ formatCompactDate() }}</time>
          <span class="whitespace-nowrap">
            {{ agendaItemsCount }} {{ agendaItemsCount === 1 ? $t('klausimas') : $t('klausimai') }}
          </span>
        </div>
      </div>

      <div class="flex shrink-0 items-center gap-3">
        <span v-if="hasOutcomes" class="pointer-events-auto">
          <MeetingOutcomeIndicators
            :matches="meeting.vote_matches || 0"
            :mismatches="meeting.vote_mismatches || 0"
            :incomplete="meeting.incomplete_vote_data || 0"
          />
        </span>
        <IFluentArrowRight20Regular class="hidden size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-brand sm:block" />
      </div>
    </div>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import MeetingOutcomeIndicators from './MeetingOutcomeIndicators.vue';

import { formatStaticTime } from '@/Utils/IntlTime';
import { DatePlate } from '@/Components/Public/Base';
import IFluentArrowRight20Regular from '~icons/fluent/arrow-right-20-regular';

// Typesense search result document structure
interface MeetingSearchDocument {
  id: string | number;
  title?: string;
  description?: string;
  start_time: number; // Unix timestamp in seconds
  start_time_formatted?: string;
  year?: number;
  month?: number;
  institution_id?: string | number;
  institution_name_lt?: string;
  institution_name_en?: string;
  tenant_shortname?: string;
  agenda_items_count?: number;
  // Vote alignment fields
  vote_matches?: number;
  vote_mismatches?: number;
  incomplete_vote_data?: number;
  vote_alignment_status?: 'all_match' | 'mixed' | 'all_mismatch' | 'neutral';
  is_recent?: boolean;
  [key: string]: unknown;
}

const props = defineProps<{
  meeting: MeetingSearchDocument;
}>();

const page = usePage();
const locale = computed(() => page.props.app?.locale || 'lt');

// start_time is a Unix timestamp in seconds; DatePlate and formatCompactDate both need a Date.
const meetingDate = computed(() => new Date(props.meeting.start_time * 1000));

// Get institution name based on current locale
const institutionName = computed(() => {
  if (locale.value === 'en' && props.meeting.institution_name_en) {
    return props.meeting.institution_name_en;
  }
  return props.meeting.institution_name_lt || props.meeting.institution_name_en || '';
});

// Get agenda items count from Typesense indexed field
const agendaItemsCount = computed(() => {
  return props.meeting.agenda_items_count ?? 0;
});

// Check if meeting has vote alignment data
const hasOutcomes = computed(() => {
  return (props.meeting.vote_matches ?? 0) > 0
    || (props.meeting.vote_mismatches ?? 0) > 0
    || (props.meeting.incomplete_vote_data ?? 0) > 0;
});

// Alignment dot class based on vote_alignment_status
const alignmentDotClass = computed(() => {
  switch (props.meeting.vote_alignment_status) {
    case 'all_match':
      return 'bg-status-success';
    case 'mixed':
      return 'bg-status-warning';
    case 'all_mismatch':
      return 'bg-status-danger';
    case 'neutral':
    default:
      return 'bg-status-neutral';
  }
});

// Alignment dot tooltip
const alignmentDotTitle = computed(() => {
  switch (props.meeting.vote_alignment_status) {
    case 'all_match':
      return $t('Visi studentų balsavimai sutampa su sprendimais');
    case 'mixed':
      return $t('Dalis studentų balsavimų sutampa su sprendimais');
    case 'all_mismatch':
      return $t('Studentų balsavimai nesutampa su sprendimais');
    case 'neutral':
    default:
      return $t('Nėra balsavimo duomenų');
  }
});

// Full localized date + time — DatePlate shows only day/month, so the archive (which spans many
// years) still needs the year spelled out somewhere.
const formatCompactDate = () => formatStaticTime(meetingDate.value, {
  year: 'numeric',
  month: 'long',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
}, locale.value as 'lt' | 'en');

// Build meeting detail URL
const getMeetingUrl = () => {
  const subdomain = page.props.tenant?.subdomain;
  return route('publicMeetings.show', {
    meeting: props.meeting.id,
    ...(subdomain ? { subdomain } : {}),
  });
};

// Build institution URL
const getInstitutionUrl = () => {
  const subdomain = page.props.tenant?.subdomain;
  return route('contacts.institution', {
    institution: props.meeting.institution_id,
    lang: locale.value,
    ...(subdomain ? { subdomain } : {}),
  });
};
</script>
