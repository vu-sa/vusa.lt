<template>
  <div>
    <QuickActionButton strong @click="showModal = true">{{ $t("Pranešti apie posėdį") }}
      <template #icon>
        <IFluentPeopleTeamAdd24Filled />
      </template>
    </QuickActionButton>
    <NewMeetingModal :show-modal="showModal" @close="showModal = false" />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

import NewMeetingModal from "../Modals/NewMeetingModal.vue";
import QuickActionButton from "./QuickActionButton.vue";

const showModal = ref(false);
</script>
