<template>
  <NSpin :show="spinning">
    <NCard size="small" class="my-2 cursor-pointer overflow-hidden shadow-xs" style="border-radius: 0.5em" :segmented="{
      content: 'soft',
    }" hoverable as="button"><template #header>
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
      </template>
      <template #header-extra>
        <MoreOptionsButton small delete @delete-click="handleDelete" />
      </template>

      <div class="text-xs text-zinc-700 dark:text-zinc-500">
        <div class="flex gap-2">
          <div class="inline-flex items-center gap-1">
            <NIcon :depth="3" size="10" :component="Icons.SHAREPOINT_FILE" />
            <span>{{ meeting.files?.length }}</span>
          </div>
          <div class="inline-flex items-center gap-1">
            <NIcon :depth="3" size="10" :component="Icons.COMMENT" />
            <span>{{ meeting.comments?.length }}</span>
          </div>
          <div class="inline-flex items-center gap-1">
            <NIcon :depth="3" size="10" :component="Icons.TASK" />
            <span>{{ completedTasks }} / {{ meeting.tasks?.length }}</span>
          </div>
        </div>
      </div>
    </NCard>
  </NSpin>
</template>

<script setup lang="tsx">
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import Icons from "@/Types/Icons/filled";

import { formatStaticTime } from "@/Utils/IntlTime";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";

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
