<template>
  <NCard size="small" :segmented="{ content: true, footer: 'soft' }" class="bg-red cursor-pointer shadow-sm" hoverable
    as="button" @click="router.visit(route('matters.show', matter.id))"><template #header>
      <NEllipsis>
        {{ matter.title }}
      </NEllipsis>
      <div class="flex items-center gap-1 text-xs text-zinc-400">
        <IFluentCalendarClock24Filled />
        <time>{{
          formatStaticTime(matter.created_at * 1000, {
            year: "numeric",
            month: "long",
          })
        }}</time>
      </div>
    </template>
    <template #header-extra>
      <NButton circle size="small" quaternary @click.stop>
        <template #icon>
          <IFluentMoreHorizontal24Filled />
        </template>
      </NButton>
    </template>
    <div v-if="matter.goal" class="flex items-center gap-1 text-xs">
      <IFluentNotebookQuestionMark24Filled />
      <span class="line-clamp-1">{{ matter.goal.title }}</span>
    </div>
    <div v-if="matter.doings_count" class="inline-flex items-center gap-1 text-xs">
      <IFluentSparkle20Filled />
      <span>{{ matter.doings_count }}</span>
    </div>
    <div class="text-xs">
      <slot />
    </div>
    <template #footer>
      <div class="flex items-end justify-between gap-2">
        <span class="my-auto text-xs text-zinc-500">#{{ matter.id }}</span>
        <DoingsStatusDonut v-if="false" :meetings="matter.meetings" :width="30" :height="30" />
      </div>
    </template>
  </NCard>
</template>

<script setup lang="tsx">
import { router } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import DoingsStatusDonut from "@/Components/Graphs/DoingsStatusDonut.vue";

defineProps<{
  matter: App.Entities.Matter;
}>();
</script>
