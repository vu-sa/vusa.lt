<template>
  <NButton :strong="strong" :secondary="secondary" :theme-overrides="{ iconMarginMedium: '0 0 4px 0' }"
    style="height: 96px; width: 144px" :type="buttonType">
    <template #icon>
      <slot name="icon" />
    </template>
    <div class="leading-4">
      <slot />
    </div>
  </NButton>
</template>

<script setup lang="ts">
import type { Type } from "naive-ui/es/button/src/interface";

defineProps<{
  buttonType?: Type;
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
</style>
