<template>
  <NCard
    size="small"
    class="my-2 cursor-pointer overflow-hidden shadow-sm"
    style="border-radius: 0.75em"
    hoverable
    as="button"
    @click="Inertia.visit(route('doings.show', doing.id))"
    ><template #header>{{ doing.title }}</template>
    <template #footer>
      <!-- <div class="flex items-center justify-between gap-2"> -->
      <!-- <StatusTag :status="doing.status"></StatusTag> -->
      <!-- <div class="inline-flex items-center gap-1">
          <NIcon :component="Sparkle20Filled" /><span>{{
            doing.doings_count
          }}</span>
        </div> -->
      <!-- </div> -->
    </template>
    <div class="text-xs text-zinc-700 dark:text-zinc-500">
      <div class="flex items-center gap-1">
        <NIcon :depth="3" size="10" :component="CalendarClock24Filled" />
        <time class="">{{ doing.created_at }}</time>
      </div>
      <div class="flex gap-2">
        <div class="inline-flex items-center gap-1">
          <NIcon :depth="3" size="10" :component="Document24Filled" />
          <span>{{ doing.documents.length }}</span>
        </div>
        <div class="inline-flex items-center gap-1">
          <NIcon :depth="3" size="10" :component="Comment24Filled" />
          <span>{{ doing.comments.length }}</span>
        </div>
        <div class="inline-flex items-center gap-1">
          <NIcon :depth="3" size="10" :component="TaskListLtr20Regular" />
          <span>{{ completedTasks }} / {{ doing.tasks.length }}</span>
        </div>
      </div>
    </div>
    <div class="absolute -bottom-8 right-0 opacity-10">
      <NIcon size="80" :color="iconColor" :depth="1">
        <component :is="icon"></component>
      </NIcon>
    </div>
  </NCard>
</template>

<script setup lang="tsx">
import {
  CalendarClock24Filled,
  Comment24Filled,
  Document24Filled,
  MailArrowUp24Filled,
  PeopleCommunity28Filled,
  PersonChat24Regular,
  Sparkle20Filled,
  TaskListLtr20Regular,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NCard, NIcon } from "naive-ui";
import { computed } from "vue";
import route from "ziggy-js";

import StatusTag from "@/Components/Tags/StatusTag.vue";

const props = defineProps<{
  doing: App.Models.Doing;
}>();

const iconColor = computed(() => {
  let status = props.doing.status;

  switch (status) {
    case "Sukurtas":
      return "#2080f0";
    case "Pabaigtas":
      return "#18a058";
    case "Atmestas":
      return "#bd2835";
    default:
      return "#bd2835";
  }
});

const icon = computed(() => {
  let types = props.doing.types;

  if (types === undefined) {
    return Sparkle20Filled;
  }

  if (
    types?.some((type) => {
      return type.title === "Posėdis";
    })
  ) {
    return PeopleCommunity28Filled;
  }

  if (
    types?.some((type) => {
      return type.title === "Susitikimas";
    })
  ) {
    return PersonChat24Regular;
  }

  if (
    types?.some((type) => {
      return type.title === "Laiškas";
    })
  ) {
    return MailArrowUp24Filled;
  }

  return Sparkle20Filled;
});

const completedTasks = computed(() => {
  return props.doing.tasks.reduce(
    (acc: number, task: App.Models.Task) => (acc += task.completed_at === null),
    0
  );
});
</script>
