<template>
  <QuickContentCard class="mb-4">
    <template v-if="questionGroup">
      <div class="flex items-center gap-2">
        <Link
          class="inline-flex items-center gap-2"
          :href="route('questionGroups.show', questionGroup.id)"
        >
          <NIcon :size="36" :component="NotebookQuestionMark24Filled" />
          <span class="text-3xl font-bold">{{ questionGroup.title }}</span>
        </Link>
      </div>
      <p class="mt-2">
        Šis klausimas priklauso
        <strong>{{ questionGroup.title }}</strong> klausimo grupei.
      </p>
    </template>

    <p v-else class="mt-2">Klausimas nepriklauso jokiai klausimo grupei.</p>
    <template #action-button>
      <NButton v-if="questionGroup" secondary size="small"
        >safoiasnfioas</NButton
      >
      <NButton v-else secondary size="small">
        <template #icon
          ><NIcon :component="NotebookQuestionMark24Filled"></NIcon
        ></template>
        Pridėti?</NButton
      >
    </template>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";
import { NotebookQuestionMark24Filled } from "@vicons/fluent";
import route from "ziggy-js";

import QuickContentCard from "@/Components/Cards/QuickContentCards/QuickContentCard.vue";

defineProps<{
  questionGroup?: App.Models.QuestionGroup;
}>();
</script>
