<template>
  <NAlert
    v-if="showAlert"
    style="background-color: rgba(244, 244, 245, 0.05)"
    :title="`${$t('Įsidėmėk')}!`"
    closable
    class="mb-4"
    type="default"
    @close="$emit('alertClosed')"
  >
    <template #icon>
      <NIcon><BookExclamationMark20Filled /></NIcon>
    </template>
    <div class="pt-2"><slot /></div>
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
