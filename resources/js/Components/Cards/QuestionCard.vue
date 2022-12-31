<template>
  <NCard
    size="small"
    :segmented="{ content: true, footer: 'soft' }"
    class="bg-red my-2 max-w-lg cursor-pointer shadow-sm"
    hoverable
    as="button"
    @click="Inertia.visit(route('questions.show', question.id))"
    ><template #header>
      <NEllipsis>
        {{ question.title }}
      </NEllipsis>
      <div class="flex items-center gap-1 text-xs text-zinc-400">
        <NIcon :component="CalendarClock24Filled" />
        <time>{{ getYYYYMMMM(question.created_at * 1000) }}</time>
      </div>
    </template>
    <template #header-extra>
      <NButton circle size="small" quaternary @click.stop
        ><template #icon
          ><NIcon :component="MoreHorizontal24Filled"></NIcon></template
      ></NButton>
    </template>
    <div v-if="question.question_group" class="flex items-center gap-1 text-xs">
      <NIcon :component="NotebookQuestionMark24Filled" />
      <span class="line-clamp-1">{{ question.question_group.title }}</span>
    </div>
    <div
      v-if="question.doings_count"
      class="inline-flex items-center gap-1 text-xs"
    >
      <NIcon :component="Sparkle20Filled" />
      <span>{{ question.doings_count }}</span>
    </div>
    <div class="text-xs">
      <slot />
    </div>
    <template #footer>
      <div class="flex items-end justify-between gap-2">
        <span class="my-auto text-xs text-zinc-500">#{{ question.id }}</span>
        <DoingsStatusDonut :doings="question.doings" :width="30" :height="30" />
      </div>
    </template>
  </NCard>
</template>

<script setup lang="tsx">
import {
  CalendarClock24Filled,
  MoreHorizontal24Filled,
  NotebookQuestionMark24Filled,
  Sparkle20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NCard, NEllipsis, NIcon } from "naive-ui";
import route from "ziggy-js";

import { getYYYYMMMM } from "@/Composables/getRelativeTime";
import DoingsStatusDonut from "@/Components/Statistics/DoingsStatusDonut.vue";

defineProps<{
  question: App.Models.Question;
}>();
</script>
