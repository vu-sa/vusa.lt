<template>
  <NButton
    text
    title="Facebook"
    @click="openBlank('https://www.facebook.com/VUstudentuatstovybe')"
  >
    <template #icon><NIcon size="18" :component="FacebookF" /></template>
  </NButton>
</template>

<script setup lang="ts">
import { FacebookF } from "@vicons/fa";
import { NButton, NIcon } from "naive-ui";

const openBlank = (url: string) => {
  window.open(url, "_blank");
};
</script>
