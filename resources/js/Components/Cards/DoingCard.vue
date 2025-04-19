<template>
  <Spinner :show="spinning">
    <Card class="my-2 cursor-pointer overflow-hidden shadow-xs" style="border-radius: 0.5em" hoverable as="button">
      <CardHeader class="pb-0">
        {{ doing.title }}
        <div class="absolute right-4 top-3">
          <MoreOptionsButton small delete @delete-click="handleDelete" />
        </div>
      </CardHeader>
      <CardContent>
        <DoingStateTag class="mb-4" :doing="doing" />

        <div class="text-xs text-zinc-700 dark:text-zinc-500">
          <div class="flex items-center gap-1">
            <IFluentCalendarClock24Filled />
            <time class="">{{ formatStaticTime(new Date(doing.date)) }}</time>
          </div>
          <div class="flex gap-2">
            <div class="inline-flex items-center gap-1">
              <IFluentComment24Regular class="h-4 w-4 text-zinc-500" />
              <span>{{ doing.comments.length }}</span>
            </div>
            <div class="inline-flex items-center gap-1">
              <IFluentTaskList24Regular class="h-4 w-4 text-zinc-500" />
              <span>{{ completedTasks }} / {{ doing.tasks.length }}</span>
            </div>
          </div>
        </div>
        <div class="absolute -bottom-8 right-0 opacity-10">
          <component :is="icon" class="h-20 w-20 text-zinc-400" />
        </div>
      </CardContent>
    </Card>
  </Spinner>
</template>

<script setup lang="tsx">
import MailArrowUp24Filled from "~icons/fluent/mail-arrow-up24-filled"
import PeopleCommunity28Filled from "~icons/fluent/people-community28-filled"
import PersonChat24Regular from "~icons/fluent/person-chat24-regular"
import Sparkle20Filled from "~icons/fluent/sparkle20-filled"

import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import DoingStateTag from "../Tag/DoingStateTag.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import { Spinner } from "@/Components/ui/spinner";
import { Card, CardHeader, CardContent } from "@/Components/ui/card";

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
