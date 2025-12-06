<template>
  <span
    class="inline-flex items-center gap-1"
    :class="[getColorClass(), compact ? 'text-xs' : 'text-sm font-medium']"
  >
    <component :is="getIcon()" :class="compact ? 'h-3 w-3' : 'h-3.5 w-3.5'" />
    <span v-if="!compact">{{ getLabel() }}</span>
  </span>
</template>

<script setup lang="ts">
import { CheckIcon, XIcon, MinusIcon, ThumbsUpIcon, ThumbsDownIcon, HelpCircleIcon } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

const props = defineProps<{
  vote: 'positive' | 'negative' | 'neutral' | null | undefined;
  type?: 'vote' | 'benefit';  // Distinguish vote vs benefit
  compact?: boolean;  // Compact mode (icon only, no text)
}>();

const getIcon = () => {
  if (props.type === 'benefit') {
    // Use thumbs icons for benefits
    switch (props.vote) {
      case 'positive': return ThumbsUpIcon;
      case 'negative': return ThumbsDownIcon;
      case 'neutral': return MinusIcon;
      default: return HelpCircleIcon;  // null/undefined = question mark (no data)
    }
  }

  // Default: use check/x for votes and decisions
  switch (props.vote) {
    case 'positive': return CheckIcon;
    case 'negative': return XIcon;
    case 'neutral': return MinusIcon;
    default: return HelpCircleIcon;  // null/undefined = question mark (no data)
  }
};

const getLabel = () => {
  switch (props.vote) {
    case 'positive': return $t('Už');
    case 'negative': return $t('Prieš');
    case 'neutral': return $t('Susilaikė');
    default: return $t('—');
  }
};

const getColorClass = () => {
  switch (props.vote) {
    case 'positive': return 'text-green-600 dark:text-green-400';
    case 'negative': return 'text-red-600 dark:text-red-400';
    case 'neutral': return 'text-zinc-600 dark:text-zinc-400';
    default: return 'text-zinc-300 dark:text-zinc-500';  // null/undefined = lighter gray (no data)
  }
};
</script>
