<template>
  <QuickContentCard>
    <!-- <div class="mt-2 inline-flex items-center gap-2">
      <NIcon :size="45" :component="CalendarClock20Regular"></NIcon>

      <span class="text-5xl font-bold">
        <NNumberAnimation :from="0" :to="20"></NNumberAnimation>
      </span>
    </div> -->
    <NProgress
      class="mt-3 mb-4"
      color="#fbb01b"
      type="line"
      :percentage="percentage"
      :stroke-width="7"
    >
      <span class="inline-flex items-center text-3xl font-bold">
        <NIcon class="mr-1" :component="Sparkle20Filled"></NIcon>
        {{ doingsNotNeedingAttention }} / {{ doings.length }}</span
      >
    </NProgress>
    <p class="mt-6 flex items-center gap-2">
      <NTag size="small" :bordered="false" type="error" round
        >{{ doings.length - doingsNotNeedingAttention }} veiklos
        <template #icon><NIcon :component="Sparkle20Filled" /></template>
      </NTag>
      <span>reikalauja dėmesio!</span>
    </p>
    <template #action-button>
      <MeetingDocumentButton
        :duty-institution="dutyInstitution"
        :questions="questionsWithDoings"
      />
    </template>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import { CalendarClock20Regular, Sparkle20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NIcon, NNumberAnimation, NProgress, NTag } from "naive-ui";
import { computed, ref } from "vue";
import route from "ziggy-js";

import MeetingDocumentButton from "@/Components/Buttons/QActButtons/MeetingDocumentButton.vue";
import QuickContentCard from "@/Components/Cards/QuickContentCards/QuickContentCard.vue";

const props = defineProps<{
  questionsWithDoings: App.Models.Doing[];
  dutyInstitution: App.Models.DutyInstitution;
}>();

const doings = [].concat(
  ...props.questionsWithDoings.map((question) => {
    return question.doings;
  })
);

const doingsNotNeedingAttention = computed(() => {
  return doings.reduce(
    (acc, val) => (acc += val.needs_attention === false ? 1 : 0),
    0
  );
});

const percentage = computed(() => {
  return Math.round((doingsNotNeedingAttention.value / doings.length) * 100);
});
</script>
