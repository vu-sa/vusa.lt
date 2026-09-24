<template>
  <span
    class="inline-flex items-center gap-1"
    :class="[colorClass, compact ? 'text-xs' : 'text-sm font-medium']"
  >
    <component :is="icon" :class="compact ? 'size-3.5' : 'size-4'" aria-hidden="true" />
    <span :class="compact ? 'sr-only' : undefined">{{ label }}</span>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { getVoteTextColorClass, getVoteDisplayLabel, type VoteValue } from '@/Composables/useAgendaItemStyling';
import IFluentCheckmark24Regular from '~icons/fluent/checkmark-24-regular';
import IFluentDismiss24Regular from '~icons/fluent/dismiss-24-regular';
import IFluentQuestionCircle24Regular from '~icons/fluent/question-circle-24-regular';
import IFluentSubtract24Regular from '~icons/fluent/subtract-24-regular';
import IFluentThumbDislike24Regular from '~icons/fluent/thumb-dislike-24-regular';
import IFluentThumbLike24Regular from '~icons/fluent/thumb-like-24-regular';

/** A recorded vote value on the public pages; `compact` keeps the word for screen readers only. */
const props = defineProps<{
  vote: VoteValue;
  type?: 'vote' | 'benefit';
  compact?: boolean;
}>();

const icon = computed(() => {
  switch (props.vote) {
    case 'positive':
      return props.type === 'benefit' ? IFluentThumbLike24Regular : IFluentCheckmark24Regular;
    case 'negative':
      return props.type === 'benefit' ? IFluentThumbDislike24Regular : IFluentDismiss24Regular;
    case 'neutral':
      return IFluentSubtract24Regular;
    default:
      return IFluentQuestionCircle24Regular;
  }
});

const colorClass = computed(() => getVoteTextColorClass(props.vote));
const label = computed(() => getVoteDisplayLabel(props.vote));
</script>
