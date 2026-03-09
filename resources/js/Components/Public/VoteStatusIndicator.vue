<template>
  <span
    class="inline-flex items-center gap-1"
    :class="[colorClass, compact ? 'text-xs' : 'text-sm font-medium']"
  >
    <component :is="icon" :class="compact ? 'h-3 w-3' : 'h-3.5 w-3.5'" />
    <span v-if="!compact">{{ label }}</span>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { CheckIcon, XIcon, MinusIcon, ThumbsUpIcon, ThumbsDownIcon, HelpCircleIcon } from 'lucide-vue-next';

import { getVoteTextColorClass, getVoteDisplayLabel, type VoteValue } from '@/Composables/useAgendaItemStyling';

/**
 * VoteStatusIndicator - Display-only vote status for public views
 *
 * Shows vote value (positive/negative/neutral) with consistent styling.
 * For interactive vote selection, use VoteSelectionBadge (admin component).
 */
const props = defineProps<{
  vote: VoteValue;
  type?: 'vote' | 'benefit'; // Distinguish vote vs benefit icons
  compact?: boolean; // Compact mode (icon only, no text)
}>();

// Get icon based on type and value
const icon = computed(() => {
  if (props.type === 'benefit') {
    // Use thumbs icons for benefits
    switch (props.vote) {
      case 'positive':
        return ThumbsUpIcon;
      case 'negative':
        return ThumbsDownIcon;
      case 'neutral':
        return MinusIcon;
      default:
        return HelpCircleIcon; // null/undefined = question mark (no data)
    }
  }

  // Default: use check/x for votes and decisions
  switch (props.vote) {
    case 'positive':
      return CheckIcon;
    case 'negative':
      return XIcon;
    case 'neutral':
      return MinusIcon;
    default:
      return HelpCircleIcon; // null/undefined = question mark (no data)
  }
});

// Use composable for consistent color
const colorClass = computed(() => getVoteTextColorClass(props.vote));

// Use composable for consistent label
const label = computed(() => getVoteDisplayLabel(props.vote));
</script>
