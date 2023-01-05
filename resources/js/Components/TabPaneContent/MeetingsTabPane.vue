<template>
  <div class="grid grid-cols-3 gap-x-4 xl:grid-cols-4">
    <MeetingCard
      v-for="meeting in meetings"
      :key="meeting.id"
      :meeting="meeting"
    ></MeetingCard>
    <div
      v-if="matter"
      role="button"
      class="mx-1 my-2 flex flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-zinc-700 p-2 text-zinc-500 duration-200 hover:shadow-inner dark:bg-zinc-900/60"
      @click="showModal = true"
    >
      <NIcon
        size="24"
        :depth="5"
        :component="DeviceMeetingRoomRemote24Regular"
      ></NIcon>
      <span>Sukurti veiklą?</span>
    </div>
  </div>
  <CardModal
    v-if="matter"
    v-model:show="showModal"
    title="Sukurti veiklą"
    @close="showModal = false"
  >
    <MeetingForm
      :meeting="meetingTemplate"
      :matter="matter"
      :meeting-types="meetingTypes"
      model-route="meetings.store"
      @success="showModal = false"
    />
  </CardModal>
</template>

<script setup lang="tsx">
import { DeviceMeetingRoomRemote24Regular } from "@vicons/fluent";
import { NIcon } from "naive-ui";
import { ref } from "vue";

import CardModal from "@/Components/Modals/CardModal.vue";
import MeetingCard from "@/Components/Cards/MeetingCard.vue";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";

defineProps<{
  meetings: App.Models.InstitutionMeeting[];
  meetingTemplate: any;
  meetingTypes: any;
  matter: App.Models.InstitutionMatter;
}>();

const showModal = ref(false);
</script>
