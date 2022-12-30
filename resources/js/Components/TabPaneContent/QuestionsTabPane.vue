<template>
  <div class="grid grid-cols-3 gap-x-4 xl:grid-cols-4">
    <QuestionCard
      v-for="question in questions"
      :key="question.id"
      :question="question"
    ></QuestionCard>
    <div
      v-if="institution"
      role="button"
      class="mx-1 my-2 flex h-36 flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-zinc-700 p-2 text-zinc-500 duration-200 hover:shadow-inner dark:bg-zinc-900/60"
      @click="showModal = true"
    >
      <NIcon size="40" :depth="5" :component="BookQuestionMark20Filled"></NIcon>
      <span>Sukurti klausimą</span>
    </div>
  </div>
  <CardModal
    v-if="institution"
    v-model:show="showModal"
    title="Sukurti klausimą"
    @close="showModal = false"
  >
    <QuestionForm
      :form="questionForm"
      :duty-institution="institution"
      @question-stored="showModal = false"
    />
  </CardModal>
</template>

<script setup lang="tsx">
import { BookQuestionMark20Filled } from "@vicons/fluent";
import { NIcon } from "naive-ui";
import { ref } from "vue";

import CardModal from "@/Components/Modals/CardModal.vue";
import QuestionCard from "@/Components/Cards/QuestionCard.vue";
import QuestionForm from "@/Components/AdminForms/QuestionForm.vue";

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
