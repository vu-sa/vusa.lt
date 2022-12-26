<template>
  <div class="grid grid-cols-4 gap-x-4">
    <NCard
      v-for="question in questions"
      :key="question.id"
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
    <div
      v-if="institution"
      role="button"
      class="mx-1 my-2 flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-zinc-700 p-2 text-zinc-500 shadow-sm duration-200 hover:shadow-lg dark:bg-zinc-900/60"
      @click="showModal = true"
    >
      <NIcon size="40" :depth="5" :component="BookQuestionMark20Filled"></NIcon>
      <span>Sukurti klausimą</span>
    </div>
  </div>
  <NModal
    v-if="institution"
    v-model:show="showModal"
    class="prose prose-sm max-w-xl dark:prose-invert"
    title="Sukurti klausimą"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
  >
    <QuestionForm
      :form="questionForm"
      :duty-institution="institution"
      @question-stored="showModal = false"
    />
  </NModal>
</template>

<script setup lang="tsx">
import {
  BookQuestionMark20Filled,
  CalendarClock24Filled,
  Sparkle20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NCard, NIcon, NModal } from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import { getYYYYMMMM } from "@/Composables/getRelativeTime";
import QuestionForm from "@/Components/AdminForms/QuestionForm.vue";
import StatusTag from "@/Components/Tags/StatusTag.vue";

defineProps<{
  questions: App.Models.Question[];
  institution: App.Models.DutyInstitution;
}>();

const showModal = ref(false);

const questionForm = {
  title: "",
  description: "",
  status: "",
};
</script>
