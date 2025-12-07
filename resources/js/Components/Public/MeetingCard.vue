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
            </div>
          </div>

          <!-- Completion status -->
          <Badge
            :variant="getCompletionVariant(meeting.completion_status)"
            size="sm"
            class="shrink-0"
          >
            {{ getCompletionLabel(meeting.completion_status) }}
          </Badge>
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
            <p class="font-medium text-zinc-900 dark:text-zinc-100 mb-1">
              {{ item.order }}. {{ item.title }}
            </p>
            <!-- Vote details only when at least one value exists -->
            <div v-if="hasDecisionData(item)" class="flex gap-4 text-zinc-500 dark:text-zinc-400">
              <span class="flex items-center gap-1">
                {{ $t('Studentų balsas') }}:
                <VoteIndicator :vote="item.student_vote" type="vote" compact />
              </span>
              <span class="flex items-center gap-1">
                {{ $t('Sprendimas') }}:
                <VoteIndicator :vote="item.decision" type="vote" compact />
              </span>
              <span class="flex items-center gap-1">
                {{ $t('Nauda') }}:
                <VoteIndicator :vote="item.student_benefit" type="benefit" compact />
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
import { Badge } from '@/Components/ui/badge';
import { ArrowRightIcon } from 'lucide-vue-next';
import AgendaOutcomeIndicators from './AgendaOutcomeIndicators.vue';
import VoteIndicator from './VoteIndicator.vue';
import SmartLink from './SmartLink.vue';
import { formatStaticTime } from '@/Utils/IntlTime';
import { useMeetingStatus } from '@/Composables/useMeetingStatus';

const $page = usePage();

const props = withDefaults(defineProps<{
  meeting: App.Entities.Meeting;
  showInstitution?: boolean;  // Show institution name (for search results)
}>(), {
  showInstitution: false
});

// All agenda items count
const allAgendaItems = computed(() => {
  return props.meeting.agenda_items || [];
});

// Items with at least one decision field filled (for outcome indicators)
const itemsWithDecisions = computed(() => {
  return allAgendaItems.value.filter(item =>
    item.student_vote !== null || item.decision !== null || item.student_benefit !== null
  );
});

// Check if an agenda item has any decision data to show
const hasDecisionData = (item: App.Entities.AgendaItem) => {
  return item.student_vote !== null || item.decision !== null || item.student_benefit !== null;
};

const formatMeetingDate = (date: string) => {
  return formatStaticTime(new Date(date), {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Use shared meeting status utilities
const { getCompletionVariant, getCompletionLabel } = useMeetingStatus();
</script>
