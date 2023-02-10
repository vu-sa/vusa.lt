<template>
  <div>
    <QuickActionButton
      :icon="PeopleTeamAdd24Filled"
      @click="showModal = true"
      >{{ $t("Pranešti apie posėdį") }}</QuickActionButton
    >
    <NMessageProvider
      ><NewMeetingModal
        :show-modal="showModal"
        @close="showModal = false"
      ></NewMeetingModal>
    </NMessageProvider>
  </div>
</template>

<script setup lang="tsx">
import { NMessageProvider } from "naive-ui";
import { PeopleTeamAdd24Filled } from "@vicons/fluent";
import { ref } from "vue";

import NewMeetingModal from "../Modals/NewMeetingModal.vue";
import QuickActionButton from "./QuickActionButton.vue";

const showModal = ref(false);
</script>
