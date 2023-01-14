<template>
  <div class="h-24 w-36">
    <NButton
      :strong="strong"
      :secondary="secondary"
      style="height: 100%; width: 100%"
      :type="buttonType"
      ><template #icon><NIcon size="20" :component="icon" /></template><slot
    /></NButton>
  </div>
</template>

<script setup lang="ts">
import { NButton, NIcon } from "naive-ui";
import type { Component } from "vue";

defineProps<{
  buttonType?: any;
  icon: Component;
  secondary?: boolean;
  strong?: boolean;
}>();
</script>

<style scoped>
.n-button {
  flex-direction: column;
  white-space: normal;
}

.n-button__content {
  white-space: normal;
}

.n-button .n-button__icon {
  margin-right: 0em !important;
}
</style>
