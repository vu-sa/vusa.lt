<template>
  <Spinner :show="spinning">
    <Card size="sm" class="my-2 cursor-pointer overflow-hidden shadow-xs dark:bg-zinc-800/90"
      style="border-radius: 0.5em" hoverable as="button">
      <CardHeader class="pb-1">
        <h4 class="mb-0 text-sm">
          {{ formatStaticTime(new Date(meeting.start_time)) }}
          {{
            institution
              ? `${institution.short_name ?? institution.name} posėdis`
              : "posėdis"
          }}
        </h4>
        <div class="flex items-center gap-1 text-xs">
          <IFluentClock24Filled />
          <time class="">{{
            formatStaticTime(new Date(meeting.start_time), {
              hour: "2-digit",
              minute: "2-digit",
            })
          }}</time>
        </div>
        <div class="absolute right-4 top-3">
          <MoreOptionsButton small delete @delete-click="handleDelete" />
        </div>
      </CardHeader>

      <CardContent class="pt-2">
        <div class="text-xs text-zinc-700 dark:text-zinc-500">
          <div class="flex gap-2">
            <div class="inline-flex items-center gap-1">
              <IFluentDocumentArrowDown24Regular class="h-4 w-4 text-zinc-500" />
              <span>{{ meeting.files?.length }}</span>
            </div>
            <div class="inline-flex items-center gap-1">
              <IFluentComment24Regular class="h-4 w-4 text-zinc-500" />
              <span>{{ meeting.comments?.length }}</span>
            </div>
            <div class="inline-flex items-center gap-1">
              <IFluentTaskList24Regular class="h-4 w-4 text-zinc-500" />
              <span>{{ completedTasks }} / {{ meeting.tasks?.length }}</span>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </Spinner>
</template>

<script setup lang="tsx">
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import { Spinner } from "@/Components/ui/spinner";
import { Card, CardHeader, CardContent } from "@/Components/ui/card";

const props = defineProps<{
  meeting: App.Entities.Meeting;
  institution?: App.Entities.Institution;
}>();

const spinning = ref(false);

const handleDelete = () => {
  spinning.value = true;
  router.delete(route("meetings.destroy", props.meeting.id), {
    preserveScroll: true,
  });
};

const completedTasks = computed(() => {
  return props.meeting.tasks.reduce(
    (acc: number, task: App.Entities.Task) =>
      (acc += task.completed_at !== null),
    0
  );
});
</script>
