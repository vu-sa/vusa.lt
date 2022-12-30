<template>
  <NButton secondary size="small" @click="showModal = true">
    <template #icon
      ><NIcon :component="NotebookQuestionMark24Filled"></NIcon
    ></template>
    <slot>Pridėti?</slot></NButton
  >
  <CardModal
    v-model:show="showModal"
    display-directive="show"
    title="Pakeisti klausimo grupę"
    @close="showModal = false"
    ><QuestionGroupSelector
      :question="question"
      @close-modal="showModal = false"
  /></CardModal>
</template>

<script setup lang="tsx">
import { NButton, NIcon } from "naive-ui";
import { NotebookQuestionMark24Filled } from "@vicons/fluent";
import { ref } from "vue";

import CardModal from "@/Components/Modals/CardModal.vue";
import QuestionGroupSelector from "@/Components/AdminForms/QuestionGroupSelector.vue";

defineProps<{
  question: App.Models.Question;
}>();

const showModal = ref(false);
</script>
