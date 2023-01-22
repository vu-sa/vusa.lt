<template>
  <div class="grid grid-cols-ramFill gap-4">
    <MeetingCard
      v-for="meeting in meetings"
      :key="meeting.id"
      class="max-w-sm"
      :meeting="meeting"
    ></MeetingCard>
    <NewGridItemButton
      v-if="institution"
      :icon="AddCircle24Filled"
      @click="showModal = true"
    >
    </NewGridItemButton>
  </div>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import { ref } from "vue";
import Icons from "@/Types/Icons/filled";

import { AddCircle24Filled } from "@vicons/fluent";
import CardModal from "@/Components/Modals/CardModal.vue";
import MeetingCard from "@/Components/Cards/MeetingCard.vue";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import NewGridItemButton from "../Buttons/NewGridItemButton.vue";

defineProps<{
  meetings: App.Entities.Meeting[];
  institution?: App.Entities.Institution;
}>();

const showModal = ref(false);
</script>
