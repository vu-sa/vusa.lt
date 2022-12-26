<template>
  <NCard
    size="small"
    segmented
    class="bg-red my-2 cursor-pointer shadow-sm"
    style="border-radius: 0.75em"
    hoverable
    as="button"
    @click="Inertia.visit(route('questions.show', question.id))"
    ><template #header>{{ question.title }}</template>
    <template #footer>
      <div class="flex items-center justify-between gap-2">
        <StatusTag :status="question.status"></StatusTag>
        <div class="inline-flex items-center gap-1">
          <NIcon :component="Sparkle20Filled" /><span>{{
            question.doings_count
          }}</span>
        </div>
      </div>
    </template>
    <div class="flex items-center gap-1">
      <NIcon :component="CalendarClock24Filled" />
      <time>{{ getYYYYMMMM(question.created_at * 1000) }}</time>
    </div>
  </NCard>
</template>

<script setup lang="tsx">
import { CalendarClock24Filled, Sparkle20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NCard, NIcon } from "naive-ui";
import route from "ziggy-js";

import { getYYYYMMMM } from "@/Composables/getRelativeTime";
import StatusTag from "@/Components/Tags/StatusTag.vue";

defineProps<{
  question: App.Models.Question;
}>();
</script>
