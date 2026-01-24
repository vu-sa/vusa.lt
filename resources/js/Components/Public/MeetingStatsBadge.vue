<template>
  <div class="inline-flex items-center gap-2 text-xs text-muted-foreground">
    <span>
      {{ meeting.agenda_items?.length || 0 }}
      {{ meeting.agenda_items?.length === 1 ? $t('klausimas') : $t('klausimai') }}
    </span>

    <span v-if="hasCompletedItems" class="inline-flex items-center gap-1">
      <span aria-hidden="true">·</span>
      <span :class="successRateClass">
        {{ successRate }}% {{ $t('priimta') }}
      </span>
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { getMainVote, calculateSuccessRate, getSuccessRateColorClass } from '@/Composables/useAgendaItemStyling';

const props = defineProps<{
  meeting: App.Entities.Meeting;
}>();

const hasCompletedItems = computed(() => {
  return props.meeting.agenda_items?.some((item) => {
    const mainVote = getMainVote(item);
    return mainVote?.student_vote && mainVote?.decision && mainVote?.student_benefit;
  }) ?? false;
});

// Use composable for success rate calculation
const successRate = computed(() => {
  return calculateSuccessRate(props.meeting.agenda_items ?? []);
});

// Use composable for success rate color
const successRateClass = computed(() => {
  return getSuccessRateColorClass(successRate.value);
});
</script>
