<template>
  <!-- Meeting card with gradient styling - fully clickable -->
  <SmartLink
    :href="route('publicMeetings.show', { meeting: meeting.id, subdomain: $page.props.tenant?.subdomain })"
    class="group relative block overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 ring-1 ring-zinc-200/50 transition-all duration-300 hover:ring-zinc-300 hover:shadow-lg dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50 dark:hover:ring-zinc-600 cursor-pointer"
  >
    <!-- Compact header -->
    <div class="p-4 pb-3">
      <div class="flex items-start justify-between gap-4 mb-2">
        <!-- Date & Time -->
        <div class="flex-1 min-w-0">
          <time class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 block">
            {{ formatMeetingDate(meeting.start_time) }}
          </time>

          <!-- Institution name (when shown in search context) -->
          <div v-if="showInstitution && meeting.institutions?.[0]" class="mt-1">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
              {{ meeting.institutions[0].name }}
            </span>
          </div>

          <!-- Outcome indicators (replacing progress bar) -->
          <div class="mt-1.5 flex items-center gap-2">
            <span class="text-xs text-zinc-500 dark:text-zinc-400">
              {{ allAgendaItems.length }}
              {{ allAgendaItems.length === 1 ? $t('klausimas') : $t('klausimai') }}
            </span>
            <AgendaOutcomeIndicators :agenda-items="itemsWithDecisions" />
            <!-- Vote alignment summary badge -->
            <span
              v-if="meetingSummary.voteAlignmentStatus !== 'unknown' && meetingSummary.totalItems > 0"
              class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-1.5 py-0.5"
              :class="{
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': meetingSummary.voteAlignmentStatus === 'all_match',
                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': meetingSummary.voteAlignmentStatus === 'mixed',
                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': meetingSummary.voteAlignmentStatus === 'all_mismatch',
                'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400': meetingSummary.voteAlignmentStatus === 'neutral',
              }"
            >
              <CheckIcon v-if="meetingSummary.voteAlignmentStatus === 'all_match'" class="h-2.5 w-2.5" />
              <AlertTriangleIcon v-else-if="meetingSummary.voteAlignmentStatus === 'mixed'" class="h-2.5 w-2.5" />
              <XIcon v-else-if="meetingSummary.voteAlignmentStatus === 'all_mismatch'" class="h-2.5 w-2.5" />
              <MinusIcon v-else class="h-2.5 w-2.5" />
              {{ meetingSummary.voteAlignmentStatus === 'all_match' ? $t('Pozicija priimta') :
                meetingSummary.voteAlignmentStatus === 'all_mismatch' ? $t('Pozicija nepriimta') :
                meetingSummary.voteAlignmentStatus === 'mixed' ? $t('Mišrus rezultatas') : $t('Neutralu') }}
            </span>
          </div>
        </div>
      </div>

      <!-- View action indicator -->
      <div class="flex justify-end">
        <span class="text-xs text-zinc-500 dark:text-zinc-400 group-hover:text-vusa-red transition-colors flex items-center gap-1">
          {{ $t('Peržiūrėti') }}
          <ArrowRightIcon class="h-3 w-3 transition-transform group-hover:translate-x-0.5" />
        </span>
      </div>
    </div>

    <!-- Agenda items (showing all items, vote details only when available) -->
    <div
      v-if="allAgendaItems.length > 0"
      class="border-t border-zinc-200/50 bg-zinc-100/50 px-4 py-3 dark:border-zinc-700/50 dark:bg-zinc-800/50"
    >
      <div class="space-y-2">
        <div
          v-for="item in allAgendaItems"
          :key="item.id"
          class="text-xs"
        >
          <div class="flex items-center gap-2 mb-1">
            <p class="font-medium text-zinc-900 dark:text-zinc-100 flex-1">
              {{ item.order }}. {{ item.title }}
            </p>
            <span
              v-if="item.brought_by_students"
              class="shrink-0 inline-flex items-center gap-0.5 rounded-full bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0 text-[10px] font-medium text-zinc-600 dark:text-zinc-300"
            >
              <UsersIcon class="h-2.5 w-2.5" />
              {{ $t('Įtraukta studentų') }}
            </span>
          </div>
          <!-- Vote details only when at least one value exists -->
          <div v-if="hasDecisionData(item)" class="flex gap-4 text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-1">
              {{ $t('Studentų balsas') }}:
              <VoteStatusIndicator :vote="getMainVote(item)?.student_vote" type="vote" compact />
            </span>
            <span class="flex items-center gap-1">
              {{ $t('Sprendimas') }}:
              <VoteStatusIndicator :vote="getMainVote(item)?.decision" type="vote" compact />
            </span>
            <span class="flex items-center gap-1">
              {{ $t('Nauda') }}:
              <VoteStatusIndicator :vote="getMainVote(item)?.student_benefit" type="benefit" compact />
            </span>
          </div>
        </div>
      </div>
    </div>
  </SmartLink>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRightIcon, Users as UsersIcon, Check as CheckIcon, AlertTriangle as AlertTriangleIcon, X as XIcon, Minus as MinusIcon } from 'lucide-vue-next';

import AgendaOutcomeIndicators from './AgendaOutcomeIndicators.vue';
import VoteStatusIndicator from './VoteStatusIndicator.vue';
import SmartLink from './SmartLink.vue';

import { Badge } from '@/Components/ui/badge';
import { formatStaticTime } from '@/Utils/IntlTime';
import { getMainVote, getMeetingStatusSummary, hasDecisionData } from '@/Composables/useAgendaItemStyling';

const $page = usePage();

const props = withDefaults(defineProps<{
  meeting: App.Entities.Meeting;
  showInstitution?: boolean; // Show institution name (for search results)
}>(), {
  showInstitution: false,
});

// All agenda items count
const allAgendaItems = computed(() => {
  return props.meeting.agenda_items || [];
});

// Meeting summary for alignment status
const meetingSummary = computed(() => {
  return getMeetingStatusSummary(allAgendaItems.value);
});

// Items with at least one decision field filled (for outcome indicators)
const itemsWithDecisions = computed(() => {
  return allAgendaItems.value.filter(hasDecisionData);
});

const formatMeetingDate = (date: string) => {
  return formatStaticTime(new Date(date), {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>
