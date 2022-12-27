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
      <div class="flex items-center justify-between gap-2">
        <StatusTag :status="doing.status"></StatusTag>
        <!-- <div class="inline-flex items-center gap-1">
          <NIcon :component="Sparkle20Filled" /><span>{{
            doing.doings_count
          }}</span>
        </div> -->
      </div>
    </template>
    <div class="flex items-center gap-1">
      <NIcon :depth="3" size="10" :component="CalendarClock24Filled" />
      <time class="text-xs text-zinc-700 dark:text-zinc-500">{{
        doing.created_at
      }}</time>
    </div>
    <div class="absolute -bottom-8 right-0 opacity-10">
      <NIcon size="80" :depth="4">
        <component :is="icon"></component>
      </NIcon>
    </div>
  </NCard>
</template>

<script setup lang="tsx">
import {
  CalendarClock24Filled,
  MailArrowUp24Filled,
  PeopleCommunity28Filled,
  PersonChat24Regular,
  Sparkle20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NCard, NIcon } from "naive-ui";
import { computed } from "vue";
import route from "ziggy-js";

import StatusTag from "@/Components/Tags/StatusTag.vue";

const props = defineProps<{
  doing: App.Models.Doing;
}>();

const icon = computed(() => {
  let types = props.doing.types;

  console.log(
    types?.some((type) => {
      return type.title === "Posėdis";
    })
  );

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
</script>
