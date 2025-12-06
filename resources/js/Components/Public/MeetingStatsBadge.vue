<template>
  <div class="inline-flex items-center gap-2 text-xs text-muted-foreground">
    <span>
      {{ meeting.agenda_items?.length || 0 }}
      {{ meeting.agenda_items?.length === 1 ? $t('klausimas') : $t('klausimai') }}
    </span>

    <span v-if="hasCompletedItems" class="inline-flex items-center gap-1">
      <span aria-hidden="true">·</span>
      <span :class="getSuccessRateClass()">
        {{ calculateStudentSuccessRate() }}% {{ $t('priimta') }}
      </span>
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

const props = defineProps<{
  meeting: App.Entities.Meeting;
}>();

const hasCompletedItems = computed(() => {
  return props.meeting.agenda_items?.some(item =>
    item.student_vote && item.decision && item.student_benefit
  ) ?? false;
});

const calculateStudentSuccessRate = () => {
  const items = props.meeting.agenda_items ?? [];
  if (items.length === 0) return 0;

  const itemsWithVotes = items.filter(item => item.student_vote && item.decision);
  if (itemsWithVotes.length === 0) return 0;

  const successfulItems = itemsWithVotes.filter(
    item => item.student_vote === item.decision
  );

  return Math.round((successfulItems.length / itemsWithVotes.length) * 100);
};

const getSuccessRateClass = () => {
  const rate = calculateStudentSuccessRate();
  if (rate >= 75) return 'text-green-600 dark:text-green-400 font-medium';
  if (rate >= 50) return 'text-amber-600 dark:text-amber-400 font-medium';
  return 'text-red-600 dark:text-red-400 font-medium';
};
</script>
