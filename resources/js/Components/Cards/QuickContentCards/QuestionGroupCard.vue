<template>
  <QuickContentCard class="mb-4">
    <FadeTransition mode="out-in">
      <div v-if="questionGroup">
        <div class="flex items-center gap-2">
          <Link
            class="inline-flex items-center gap-2"
            :href="route('questionGroups.show', questionGroup.id)"
          >
            <NIcon :size="30" :component="NotebookQuestionMark24Filled" />
            <span class="text-2xl font-bold line-clamp-1">{{
              questionGroup.title
            }}</span>
          </Link>
        </div>
        <p class="mt-4">
          Šis klausimas priklauso
          <strong>{{ questionGroup.title }}</strong> klausimo grupei.
        </p>
      </div>
      <p v-else class="mt-2">Klausimas nepriklauso jokiai klausimo grupei.</p>
    </FadeTransition>
    <template #action-button>
      <div class="flex items-center gap-2">
        <Link
          v-if="questionGroup"
          :href="route('questionGroups.show', questionGroup.id)"
        >
          <NButton icon-placement="right" secondary size="small"
            ><template #icon
              ><NIcon :component="ArrowUpRight24Filled" /></template
            >Eiti</NButton
          >
        </Link>
        <QuestionGroupChanger :question="question"
          ><template v-if="questionGroup"
            >Pakeisti?</template
          ></QuestionGroupChanger
        >
      </div>
    </template>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import {
  ArrowUpRight24Filled,
  NotebookQuestionMark24Filled,
} from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";
import route from "ziggy-js";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import QuestionGroupChanger from "@/Components/Buttons/QuestionGroupChanger.vue";
import QuickContentCard from "@/Components/Cards/QuickContentCards/QuickContentCard.vue";

defineProps<{
  questionGroup?: App.Models.QuestionGroup;
  question: App.Models.Question;
}>();
</script>
