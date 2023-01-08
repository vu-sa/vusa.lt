<template>
  <NButton secondary size="small" @click="showModal = true">
    <template #icon
      ><NIcon :component="StarLineHorizontal324Filled"></NIcon
    ></template>
    <slot>Pridėti?</slot></NButton
  >
  <CardModal
    v-model:show="showModal"
    display-directive="show"
    title="Pakeisti klausimo grupę"
    @close="showModal = false"
    ><GoalSelector :matter="matter" @close-modal="showModal = false"
  /></CardModal>
</template>

<script setup lang="tsx">
import { NButton, NIcon } from "naive-ui";
import { StarLineHorizontal324Filled } from "@vicons/fluent";
import { ref } from "vue";

import CardModal from "@/Components/Modals/CardModal.vue";
import GoalSelector from "@/Components/AdminForms/GoalSelectorForm.vue";

defineProps<{
  matter: App.Models.Matter;
}>();

const showModal = ref(false);
</script>
