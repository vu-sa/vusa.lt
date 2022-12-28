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
    <template #header-extra>
      <div class="inline-flex gap-2">
        <NPopover>
          <template #trigger>
            <NButton text size="small" circle @click.stop>
              <template #icon>
                <UserAvatar :user="$page.props.user" :size="24" />
              </template>
            </NButton>
          </template>
          <div class="flex flex-col gap-2">
            <NButton
              v-for="duty in institutionDuties"
              :key="duty.id"
              size="small"
              secondary
            >
              <template #icon>
                <UserAvatar :user="$page.props.user" :size="16" />
              </template>
              {{ duty.name }}</NButton
            >
          </div>
        </NPopover>
        <NButton circle size="small" quaternary @click.stop
          ><template #icon
            ><NIcon :component="MoreHorizontal24Filled"></NIcon></template
        ></NButton>
      </div>
    </template>
    <InstitutionAvatarGroup
      v-if="institution.users"
      :users="institution.users"
    />

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
  </NCard>
</template>

<script setup lang="tsx">
import { CalendarClock24Filled, MoreHorizontal24Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NAvatar, NButton, NCard, NIcon, NPopover, NTag } from "naive-ui";
import { computed } from "vue";
import route from "ziggy-js";

import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import UserAvatar from "../Avatars/UserAvatar.vue";
import getRelativeTime from "@/Composables/getRelativeTime";

const props = defineProps<{
  institution: App.Models.DutyInstitution;
  isPadalinys?: boolean;
  showLastMeeting?: boolean;
  duties: App.Models.Duty[];
}>();

const institutionDuties = computed(() => {
  return props.duties.filter((duty) => {
    return duty.institution_id === props.institution.id;
  });
});
</script>
