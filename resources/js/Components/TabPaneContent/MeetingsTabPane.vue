<template>
  <div class="grid grid-cols-ramFill gap-x-4">
    <MeetingCard
      v-for="meeting in meetings"
      :key="meeting.id"
      class="max-w-sm"
      :meeting="meeting"
      @click="router.visit(route('meetings.show', meeting.id))"
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
import { AddCircle24Filled } from "@vicons/fluent";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import MeetingCard from "@/Components/Cards/MeetingCard.vue";
import NewGridItemButton from "../Buttons/NewGridItemButton.vue";

defineProps<{
  meetings: App.Entities.Meeting[];
  institution?: App.Entities.Institution;
}>();

const showModal = ref(false);
</script>
