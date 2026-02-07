<template>
  <!-- Consensus Badge - distinct styling with student_benefit background -->
  <Badge
    v-if="vote.is_consensus"
    variant="outline"
    class="gap-1.5 text-xs font-normal px-2 py-1 h-6"
    :class="[
      consensusBadgeClass,
      vote.is_main ? 'font-semibold' : '',
    ]"
  >
    <Handshake class="h-3.5 w-3.5" />
    <span>{{ $t('Sutarimas') }}</span>
    <!-- Vote title (if exists) -->
    <span v-if="vote.title" class="opacity-80">
      · {{ vote.title }}
    </span>
  </Badge>
  
  <!-- Standard Vote Badge -->
  <Badge
    v-else
    variant="outline"
    class="gap-0 text-xs font-normal px-1.5 py-1 h-6"
    :class="badgeBackground"
  >
    <!-- Left side: Decision icon (gavel represents official decision) -->
    <div class="flex items-center">
      <Gavel :class="['h-3.5 w-3.5', decisionIconColor]" />
    </div>

    <!-- Vertical separator with margins -->
    <div :class="['w-px h-4 mx-2', separatorColor]" />

    <!-- Right side: Student vote icon (graduation cap represents students) -->
    <div class="flex items-center">
      <GraduationCap :class="['h-3.5 w-3.5', studentVoteIconColor]" />
    </div>

    <!-- Vote title/number (if exists) -->
    <span v-if="vote.title || index !== undefined && !vote.is_main" class="ml-2 text-zinc-600 dark:text-zinc-400">
      {{ vote.title || `#${index + 1}` }}
    </span>
  </Badge>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Gavel, GraduationCap, Handshake } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { useAgendaItemStyling, type VoteValue } from '@/Composables/useAgendaItemStyling';

const props = defineProps<{
  vote: App.Entities.Vote;
  index: number;
  isMain?: boolean;
}>();

const styling = useAgendaItemStyling();

// Icon color for decision (colored based on decision value)
const decisionIconColor = computed(() => getIconColorClass(props.vote.decision));

// Icon color for student vote (colored based on student_vote value)
const studentVoteIconColor = computed(() => getIconColorClass(props.vote.student_vote));

// Helper to get icon color based on vote value
function getIconColorClass(value: VoteValue): string {
  switch (value) {
    case 'positive':
      return 'text-emerald-600 dark:text-emerald-400';
    case 'negative':
      return 'text-red-600 dark:text-red-400';
    case 'neutral':
      return 'text-zinc-500 dark:text-zinc-400';
    default:
      return 'text-zinc-300 dark:text-zinc-600'; // Unset/unknown
  }
}

// Background color based on student_benefit (indicates value perception)
const badgeBackground = computed(() => {
  const bgClass = styling.getStudentBenefitBgClass(props.vote.student_benefit);
  return `${bgClass} border-zinc-200 dark:border-zinc-700 ${props.vote.is_main ? 'font-semibold border border-zinc-300 dark:border-zinc-700' : ''}`;
});

// Consensus badge class - combines teal accent with student_benefit background
const consensusBadgeClass = computed(() => {
  // Base teal styling for consensus indicator
  const baseClass = 'border-teal-400 dark:border-teal-600';
  
  // Background based on student_benefit
  switch (props.vote.student_benefit) {
    case 'positive':
      return `${baseClass} bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300`;
    case 'negative':
      return `${baseClass} bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300`;
    case 'neutral':
      return `${baseClass} bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300`;
    default:
      // Default teal when no benefit set
      return `${baseClass} bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300`;
  }
});

// Separator color that works on all backgrounds
const separatorColor = computed(() => {
  switch (props.vote.student_benefit) {
    case 'positive':
      return 'bg-emerald-300 dark:bg-emerald-700';
    case 'negative':
      return 'bg-red-300 dark:bg-red-700';
    default:
      return 'bg-zinc-300 dark:bg-zinc-600';
  }
});
</script>

