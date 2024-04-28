<template>
  <NButton title="Instagram" text @click="openBlank('https://www.instagram.com/vustudentuatstovybe/')">
    <template #icon>
      <NIcon size="14" :component="Instagram" />
    </template>
    <slot />
  </NButton>
</template>

<script setup lang="ts">
import { Instagram } from "@vicons/fa";
import { NButton, NIcon } from "naive-ui";

const openBlank = (url: string) => {
  window.open(url, "_blank");
};
</script>
