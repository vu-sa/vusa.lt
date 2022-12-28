<template>
  <div class="grid grid-cols-3 gap-x-4 xl:grid-cols-4">
    <DoingCard
      v-for="doing in doings"
      :key="doing.id"
      :doing="doing"
    ></DoingCard>
    <div
      v-if="question"
      role="button"
      class="mx-1 my-2 flex h-20 flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-zinc-700 p-2 text-zinc-500 duration-200 hover:shadow-inner dark:bg-zinc-900/60"
      @click="showModal = true"
    >
      <NIcon size="24" :depth="5" :component="Sparkle20Filled"></NIcon>
      <span>Sukurti veiklą</span>
    </div>
  </div>
  <NModal
    v-if="question"
    v-model:show="showModal"
    class="prose prose-sm max-w-xl dark:prose-invert"
    title="Sukurti veiklą"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
  >
    <DoingForm
      :doing="doingTemplate"
      :question="question"
      :doing-types="doingTypes"
      model-route="doings.store"
      @success="showModal = false"
    />
  </NModal>
</template>

<script setup lang="tsx">
import { NIcon, NModal } from "naive-ui";
import { Sparkle20Filled } from "@vicons/fluent";
import { ref } from "vue";

import DoingCard from "../Cards/DoingCard.vue";
import DoingForm from "../AdminForms/DoingForm.vue";

defineProps<{
  doings: App.Models.Doing[];
  doingTemplate: any;
  doingTypes: any;
  question: App.Models.Question;
}>();

const showModal = ref(false);
</script>
