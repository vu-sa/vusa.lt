<template>
  <NAlert
    v-if="showAlert"
    style="background-color: rgba(0, 0, 0, 0)"
    title="Įsidėmėk!"
    closable
    class="mb-4"
    type="default"
    @close="$emit('alertClosed')"
  >
    <template #icon>
      <NIcon><BookExclamationMark20Filled /></NIcon>
    </template>
    <slot />
  </NAlert>
</template>

<script setup lang="tsx">
import { BookExclamationMark20Filled } from "@vicons/fluent";
import { NAlert, NIcon } from "naive-ui";

defineEmits(["alertClosed"]);

defineProps<{
  showAlert: boolean;
}>();
</script>
