<template>
  <div
    class="group transition-all duration-200 border border-border/50 rounded-md bg-card hover:shadow-lg hover:bg-accent/20 hover:border-primary/30 cursor-pointer"
    @click="navigateToMeeting"
  >
    <div class="block sm:flex sm:items-center gap-2 sm:gap-3 px-3 sm:px-4 py-3">
      <!-- Mobile Layout: Stacked -->
      <div class="sm:hidden space-y-1.5">
        <!-- Date Row -->
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <!-- Alignment Status Dot -->
            <span
              v-if="meeting.vote_alignment_status"
              class="w-2.5 h-2.5 rounded-full flex-shrink-0"
              :class="alignmentDotClass"
              :title="alignmentDotTitle"
            />
            <time class="text-sm font-semibold text-card-foreground group-hover:text-primary transition-colors">
              {{ formatCompactDate() }}
            </time>
          </div>
          <!-- Arrow -->
          <ArrowRightIcon class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
        </div>

        <!-- Institution Row -->
        <div class="text-sm text-muted-foreground">
          <Link
            v-if="institutionName && meeting.institution_id"
            :href="getInstitutionUrl()"
            class="font-medium hover:text-primary hover:underline transition-colors"
            @click.stop
          >
            {{ institutionName }}
          </Link>
          <span v-else-if="institutionName" class="font-medium">
            {{ institutionName }}
          </span>
        </div>

        <!-- Metadata Row -->
        <div class="flex items-center gap-3 text-xs text-muted-foreground">
          <!-- Agenda Count -->
          <span class="whitespace-nowrap">
            {{ agendaItemsCount }} {{ agendaItemsCount === 1 ? $t('klausimas') : $t('klausimai') }}
          </span>

          <!-- Outcome Indicators (vote alignment) -->
          <MeetingOutcomeIndicators
            v-if="hasOutcomes"
            :matches="meeting.vote_matches || 0"
            :mismatches="meeting.vote_mismatches || 0"
            :incomplete="meeting.incomplete_vote_data || 0"
          />
        </div>
      </div>

      <!-- Desktop Layout: Horizontal -->
      <div class="hidden sm:flex sm:items-center sm:gap-4 sm:w-full">
        <!-- Alignment Status Dot -->
        <span
          v-if="meeting.vote_alignment_status"
          class="w-2.5 h-2.5 rounded-full flex-shrink-0"
          :class="alignmentDotClass"
          :title="alignmentDotTitle"
        />

        <!-- Date -->
        <time class="text-sm font-semibold text-card-foreground group-hover:text-primary transition-colors whitespace-nowrap flex-shrink-0 min-w-[200px]">
          {{ formatCompactDate() }}
        </time>

        <!-- Separator -->
        <span class="text-border">|</span>

        <!-- Institution Name (clickable link) -->
        <div class="flex-1 min-w-0">
          <Link
            v-if="institutionName && meeting.institution_id"
            :href="getInstitutionUrl()"
            class="text-sm text-muted-foreground line-clamp-1 hover:text-primary hover:underline transition-colors"
            :title="institutionName"
            @click.stop
          >
            {{ institutionName }}
          </Link>
          <span
            v-else-if="institutionName"
            class="text-sm text-muted-foreground line-clamp-1"
            :title="institutionName"
          >
            {{ institutionName }}
          </span>
        </div>

        <!-- Compact Metadata -->
        <div class="flex items-center gap-3 flex-shrink-0">
          <!-- Agenda Count -->
          <span class="text-xs text-muted-foreground whitespace-nowrap">
            {{ agendaItemsCount }} {{ agendaItemsCount === 1 ? $t('klausimas') : $t('klausimai') }}
          </span>

          <!-- Outcome Indicators (vote alignment) -->
          <MeetingOutcomeIndicators
            v-if="hasOutcomes"
            :matches="meeting.vote_matches || 0"
            :mismatches="meeting.vote_mismatches || 0"
            :incomplete="meeting.incomplete_vote_data || 0"
          />

          <!-- Arrow -->
          <ArrowRightIcon class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRightIcon } from 'lucide-vue-next';

import MeetingOutcomeIndicators from './MeetingOutcomeIndicators.vue';

import { formatStaticTime } from '@/Utils/IntlTime';

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
  [key: string]: any;
}

const props = defineProps<{
  meeting: MeetingSearchDocument;
}>();

const page = usePage();
const locale = computed(() => page.props.app?.locale || 'lt');

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
      return 'bg-green-500';
    case 'mixed':
      return 'bg-amber-500';
    case 'all_mismatch':
      return 'bg-red-700';
    case 'neutral':
    default:
      return 'bg-zinc-400';
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

// Format compact date from Unix timestamp (seconds)
const formatCompactDate = () => {
  // start_time is Unix timestamp in seconds, multiply by 1000 for JS Date
  const date = new Date(props.meeting.start_time * 1000);
  return formatStaticTime(date, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }, locale.value as 'lt' | 'en');
};

// Build meeting detail URL
const getMeetingUrl = () => {
  const subdomain = page.props.tenant?.subdomain;
  return route('publicMeetings.show', {
    meeting: props.meeting.id,
    ...(subdomain ? { subdomain } : {}),
  });
};

// Navigate to meeting (used for card click)
const navigateToMeeting = () => {
  router.visit(getMeetingUrl());
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
