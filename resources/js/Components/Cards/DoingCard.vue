<template>
  <NSpin :show="spinning">
    <NCard
      size="small"
      class="my-2 cursor-pointer overflow-hidden shadow-sm"
      style="border-radius: 0.5em"
      hoverable
      as="button"
      ><template #header>{{ doing.title }}</template>
      <template #header-extra>
        <MoreOptionsButton
          small
          delete
          @delete-click="handleDelete"
        ></MoreOptionsButton>
      </template>

      <DoingStateTag class="mb-4" :doing="doing" />

      <div class="text-xs text-zinc-700 dark:text-zinc-500">
        <div class="flex items-center gap-1">
          <NIcon :depth="3" size="10" :component="CalendarClock24Filled" />
          <time class="">{{ formatStaticTime(new Date(doing.date)) }}</time>
        </div>
        <div class="flex gap-2">
          <div class="inline-flex items-center gap-1">
            <NIcon :depth="3" size="10" :component="Icons.COMMENT" />
            <span>{{ doing.comments.length }}</span>
          </div>
          <div class="inline-flex items-center gap-1">
            <NIcon :depth="3" size="10" :component="Icons.TASK" />
            <span>{{ completedTasks }} / {{ doing.tasks.length }}</span>
          </div>
        </div>
      </div>
      <div class="absolute -bottom-8 right-0 opacity-10">
        <NIcon size="80" :depth="4">
          <component :is="icon"></component>
        </NIcon>
      </div>
    </NCard>
  </NSpin>
</template>

<script setup lang="tsx">
import {
  CalendarClock24Filled,
  MailArrowUp24Filled,
  PeopleCommunity28Filled,
  PersonChat24Regular,
  Sparkle20Filled,
} from "@vicons/fluent";
import { NCard, NIcon, NSpin, NTag } from "naive-ui";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import DoingStateTag from "../Tag/DoingStateTag.vue";
import Icons from "@/Types/Icons/filled";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";

const props = defineProps<{
  doing: App.Entities.Doing;
}>();

const spinning = ref(false);

const handleDelete = () => {
  spinning.value = true;
  router.delete(route("doings.destroy", props.doing.id), {
    preserveScroll: true,
  });
};

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
    (acc: number, task: App.Entities.Task) =>
      (acc += task.completed_at !== null),
    0
  );
});
</script>
