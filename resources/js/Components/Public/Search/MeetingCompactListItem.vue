<template>
  <div
    class="group transition-all duration-200 border border-border/50 rounded-md bg-card hover:shadow-lg hover:bg-accent/20 hover:border-primary/30 cursor-pointer"
    @click="navigateToMeeting"
  >
    <div class="block sm:flex sm:items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2">
      <!-- Mobile Layout: Stacked -->
      <div class="sm:hidden space-y-2">
        <!-- Title Row -->
        <div class="flex items-center gap-2">
          <!-- Status Dot -->
          <div
            :class="getStatusDotColor(meeting.completion_status)"
            class="w-2 h-2 rounded-full flex-shrink-0"
          />
          <!-- Date -->
          <div class="flex-1 min-w-0">
            <time class="text-xs font-medium text-card-foreground group-hover:text-primary transition-colors">
              {{ formatCompactDate() }}
            </time>
          </div>
          <!-- Arrow -->
          <ArrowRightIcon class="w-3 h-3 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
        </div>

        <!-- Metadata Row -->
        <div class="flex items-center gap-2 text-xs text-muted-foreground">
          <!-- Completion Status Badge -->
          <Badge
            :variant="getCompletionVariant(meeting.completion_status)"
            class="text-[10px] font-medium px-1.5 py-0 flex-shrink-0"
          >
            {{ getCompletionLabel(meeting.completion_status) }}
          </Badge>

          <!-- Institution (clickable link) -->
          <Link
            v-if="institutionName && meeting.institution_id"
            :href="getInstitutionUrl()"
            class="max-w-24 truncate flex-shrink-0 font-medium hover:text-primary hover:underline transition-colors"
            :title="institutionName"
            @click.stop
          >
            {{ institutionName }}
          </Link>
          <span
            v-else-if="institutionName"
            class="max-w-24 truncate flex-shrink-0 font-medium"
            :title="institutionName"
          >
            {{ institutionName }}
          </span>

          <!-- Agenda Count -->
          <span class="whitespace-nowrap flex-shrink-0">
            {{ agendaItemsCount }} {{ agendaItemsCount === 1 ? $t('kl.') : $t('kl.') }}
          </span>
        </div>
      </div>

      <!-- Desktop Layout: Horizontal -->
      <div class="hidden sm:flex sm:items-center sm:gap-3 sm:w-full">
        <!-- Status Dot -->
        <div
          :class="getStatusDotColor(meeting.completion_status)"
          class="w-2 h-2 rounded-full flex-shrink-0"
        />

        <!-- Date -->
        <time class="text-sm font-medium text-card-foreground group-hover:text-primary transition-colors whitespace-nowrap flex-shrink-0 w-40">
          {{ formatCompactDate() }}
        </time>

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
        <div class="flex items-center gap-2 flex-shrink-0 min-w-0">
          <!-- Agenda Count -->
          <span class="text-xs text-muted-foreground whitespace-nowrap">
            {{ agendaItemsCount }} {{ agendaItemsCount === 1 ? $t('klausimas') : $t('klausimai') }}
          </span>

          <!-- Outcome Indicators -->
          <MeetingOutcomeIndicators
            v-if="hasOutcomes"
            :matches="meeting.vote_matches || 0"
            :mismatches="meeting.vote_mismatches || 0"
            :incomplete="meeting.incomplete_vote_data || 0"
          />

          <!-- Completion Status Badge -->
          <Badge
            :variant="getCompletionVariant(meeting.completion_status)"
            class="text-xs font-medium px-1.5 py-0.5 flex-shrink-0"
          >
            {{ getCompletionLabel(meeting.completion_status) }}
          </Badge>

          <!-- Arrow -->
          <ArrowRightIcon class="w-3.5 h-3.5 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Badge } from '@/Components/ui/badge';
import { ArrowRightIcon } from 'lucide-vue-next';
import MeetingOutcomeIndicators from './MeetingOutcomeIndicators.vue';
import { formatStaticTime } from '@/Utils/IntlTime';
import { useMeetingStatus } from '@/Composables/useMeetingStatus';

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
  completion_status: string;
  agenda_items_count?: number;
  total_agenda_items?: number;
  completed_items?: number;
  student_success_rate?: number;
  positive_outcomes?: number;
  negative_outcomes?: number;
  neutral_outcomes?: number;
  // Vote alignment fields
  vote_matches?: number;
  vote_mismatches?: number;
  incomplete_vote_data?: number;
  has_completed_items?: boolean;
  is_recent?: boolean;
  [key: string]: any;
}

const props = defineProps<{
  meeting: MeetingSearchDocument;
}>();

const page = usePage();
const locale = computed(() => page.props.app?.locale || 'lt');

// Use shared meeting status utilities
const { getCompletionVariant, getCompletionLabel, getStatusDotColor } = useMeetingStatus();

// Get institution name based on current locale
const institutionName = computed(() => {
  if (locale.value === 'en' && props.meeting.institution_name_en) {
    return props.meeting.institution_name_en;
  }
  return props.meeting.institution_name_lt || props.meeting.institution_name_en || '';
});

// Get agenda items count from Typesense indexed field
const agendaItemsCount = computed(() => {
  return props.meeting.agenda_items_count ?? props.meeting.total_agenda_items ?? 0;
});

// Check if meeting has vote alignment data
const hasOutcomes = computed(() => {
  return (props.meeting.vote_matches ?? 0) > 0 ||
    (props.meeting.vote_mismatches ?? 0) > 0 ||
    (props.meeting.incomplete_vote_data ?? 0) > 0;
});

// Format compact date from Unix timestamp (seconds)
const formatCompactDate = () => {
  // start_time is Unix timestamp in seconds, multiply by 1000 for JS Date
  const date = new Date(props.meeting.start_time * 1000);
  return formatStaticTime(date, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Build meeting detail URL
const getMeetingUrl = () => {
  const subdomain = page.props.tenant?.subdomain;
  return route('publicMeetings.show', {
    meeting: props.meeting.id,
    ...(subdomain ? { subdomain } : {})
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
    ...(subdomain ? { subdomain } : {})
  });
};


</script>
