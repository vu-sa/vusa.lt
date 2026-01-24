<template>
  <div class="space-y-4">
    <!-- Agenda Summary Section -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
        <div class="flex items-center gap-3">
          <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $t('Darbotvarkė') }}</span>
          <span class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ agendaStatusSummary }}
          </span>
        </div>
        <Button variant="ghost" size="sm" class="gap-1 text-primary" @click="$emit('go-to-agenda')">
          {{ $t('Redaguoti') }}
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>

      <!-- Agenda Items List -->
      <div v-if="meeting.agenda_items && meeting.agenda_items.length > 0" class="divide-y divide-zinc-100 dark:divide-zinc-800">
        <button
          v-for="(item, index) in meeting.agenda_items"
          :key="item.id"
          type="button"
          class="flex w-full items-start gap-3 px-4 py-3 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors text-left cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:ring-inset"
          @click="$emit('go-to-agenda-item', item.id)"
        >
          <!-- Number Badge -->
          <span
            :class="[
              'flex items-center justify-center w-6 h-6 rounded-md text-xs font-semibold shrink-0 mt-0.5',
              styling.getNumberBadgeClass(item),
            ]"
          >
            {{ index + 1 }}
          </span>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="text-sm text-zinc-900 dark:text-zinc-100 leading-snug">
              {{ item.title }}
            </p>
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
              <!-- Status indicator -->
              <span :class="['text-xs font-medium', styling.getStatusTextClass(item)]">
                {{ styling.getStatusIcon(item) }} {{ styling.getStatusText(item) }}
              </span>
              <!-- Student vote label (only if single vote or main vote) -->
              <span v-if="getItemStudentVoteText(item)" class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ getItemStudentVoteText(item) }}
              </span>
            </div>
          </div>

          <!-- Vote Badges (show if votes exist, regardless of item type) -->
          <div v-if="item.votes && item.votes.length > 0" class="flex items-center gap-1 shrink-0">
            <VoteSelectionBadge
              v-for="(vote, voteIndex) in getDisplayVotes(item)"
              :key="vote.id || voteIndex"
              :vote
              :index="voteIndex"
              :is-main="vote.is_main"
            />
            <span v-if="getAdditionalVotesCount(item) > 0" class="text-xs text-zinc-400">
              +{{ getAdditionalVotesCount(item) }}
            </span>
          </div>
        </button>
      </div>

      <!-- Empty state -->
      <div v-else class="px-4 py-8 text-center">
        <ClipboardList class="h-8 w-8 text-zinc-300 dark:text-zinc-600 mx-auto mb-2" />
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
{{ $t('Darbotvarkė tuščia') }}
</p>
        <Button variant="outline" size="sm" class="mt-3" @click="$emit('go-to-agenda')">
          {{ $t('Pridėti punktų') }}
        </Button>
      </div>
    </div>

    <!-- Document Status - Compact -->
    <div
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3"
      :class="hasAllDocuments ? 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900' : 'border-amber-200 dark:border-amber-900/50 bg-amber-50/50 dark:bg-amber-900/20'"
    >
      <div class="flex items-center gap-3">
        <FileText class="h-5 w-5 text-zinc-500 dark:text-zinc-400 shrink-0" />
        <span class="text-sm text-zinc-700 dark:text-zinc-300">
          {{ documentStatusText }}
        </span>
      </div>
      <Button v-if="!hasAllDocuments" variant="outline" size="sm" class="gap-1.5" @click="$emit('go-to-files')">
        <Upload class="h-4 w-4" />
        {{ $t('Įkelti') }}
      </Button>
    </div>

    <!-- Meeting Navigation -->
    <MeetingNavigationCards v-if="previousMeeting || nextMeeting" :previous-meeting :next-meeting />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, ClipboardList, FileText, Upload } from 'lucide-vue-next';

import MeetingNavigationCards from './MeetingNavigationCards.vue';

import VoteSelectionBadge from '@/Components/AgendaItems/VoteSelectionBadge.vue';
import { Button } from '@/Components/ui/button';
import { useMeetingUrgency } from '@/Composables/useMeetingUrgency';
import { useAgendaItemStyling } from '@/Composables/useAgendaItemStyling';

interface MeetingNav {
  id: string;
  start_time: string;
}

const props = defineProps<{
  meeting: App.Entities.Meeting;
  representatives?: App.Entities.User[];
  previousMeeting?: MeetingNav | null;
  nextMeeting?: MeetingNav | null;
}>();

defineEmits<{
  'go-to-agenda': [];
  'go-to-agenda-item': [itemId: string];
  'go-to-files': [];
  'go-to-tasks': [];
  'edit': [];
}>();

// Use composables
const { hasProtocol, hasReport } = useMeetingUrgency(() => props.meeting);
const styling = useAgendaItemStyling();

// Constants
const MAX_DISPLAY_VOTES = 3;

// Computed values
const hasAllDocuments = computed(() => hasProtocol.value && hasReport.value);

const documentStatusText = computed(() => {
  if (hasAllDocuments.value) return $t('Protokolas ir ataskaita įkelti');
  if (!hasProtocol.value && !hasReport.value) return $t('Protokolas ir ataskaita neįkelti');
  if (!hasProtocol.value) return $t('Protokolas neįkeltas');
  return $t('Ataskaita neįkelta');
});

// Agenda summary
const agendaStatusSummary = computed(() => {
  const items = props.meeting.agenda_items ?? [];
  if (items.length === 0) return '';

  const votingItems = items.filter(item => item.type === 'voting');
  if (votingItems.length === 0) return `${items.length} ${$t('punktų')}`;

  const completedVotes = votingItems.filter((item) => {
    const mainVote = styling.getMainVote(item);
    return mainVote?.decision != null;
  }).length;

  return `${completedVotes} ${$t('iš')} ${votingItems.length} ${$t('balsavimų aptarta')}`;
});

// Get display votes (limited to MAX_DISPLAY_VOTES)
const getDisplayVotes = (item: App.Entities.AgendaItem) => {
  return (item.votes ?? []).slice(0, MAX_DISPLAY_VOTES);
};

// Get count of additional votes beyond display limit
const getAdditionalVotesCount = (item: App.Entities.AgendaItem) => {
  return Math.max(0, (item.votes?.length ?? 0) - MAX_DISPLAY_VOTES);
};

// Get student vote text (only shown when there's a single vote or no badges displayed)
const getItemStudentVoteText = (item: App.Entities.AgendaItem) => {
  if (item.type !== 'voting') return null;
  // Don't show text if we're already showing badges
  if (item.votes && item.votes.length > 0) return null;

  const mainVote = styling.getMainVote(item);
  if (!mainVote?.student_vote) return null;

  const label = styling.getStudentVoteLabel(mainVote.student_vote);
  return label ? `${$t('Studentai')}: ${label}` : null;
};
</script>
