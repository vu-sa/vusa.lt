<template>
  <NCard
    hoverable
    :segmented="{
      footer: 'soft',
    }"
    as="button"
    style="max-width: 400px"
    class="cursor-pointer shadow-sm"
    @click="Inertia.visit(route('dutyInstitutions.show', institution.id))"
  >
    <template #header>
      <span :class="{ 'font-bold': isPadalinys }">{{ institution.name }}</span>
    </template>
    <template #header-extra
      ><NButton circle size="small" quaternary @click.stop
        ><template #icon
          ><NIcon
            :component="MoreHorizontal24Filled"
          ></NIcon></template></NButton
    ></template>
    <template #footer>
      <div class="flex justify-between gap-2">
        <NTag
          v-for="type in institution.types"
          :key="type.id"
          size="small"
          :bordered="false"
        >
          {{ type.title }}
        </NTag>
        <a
          v-if="showLastMeeting"
          class="inline-flex items-center"
          target="_blank"
          :href="route('doings.show', institution.lastMeetingDoing?.id)"
          @click.stop
        >
          <NIcon class="mx-1" :component="CalendarClock24Filled"></NIcon>
          <span class="font-bold">{{
            getRelativeTime(institution.lastMeetingDoing?.date)
          }}</span>
        </a>
      </div>
    </template>
    <InstitutionAvatarGroup
      v-if="institution.users"
      :users="institution.users"
    />
  </NCard>
</template>

<script setup lang="tsx">
import { CalendarClock24Filled, MoreHorizontal24Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NCard, NIcon, NTag } from "naive-ui";
import route from "ziggy-js";

import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import getRelativeTime from "@/Composables/getRelativeTime";

defineProps<{
  institution: App.Models.DutyInstitution;
  isPadalinys?: boolean;
  showLastMeeting?: boolean;
}>();
</script>
