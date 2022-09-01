<template>
  <NButton
    text
    @click="openBlank('https://www.facebook.com/VUstudentuatstovybe')"
  >
    <NIcon size="18">
      <Instagram />
    </NIcon>
  </NButton>
</template>

<script setup lang="ts">
import { Instagram } from "@vicons/fa";
import { NButton, NIcon } from "naive-ui";

const openBlank = (url: string) => {
  window.open(url, "_blank");
};
</script>
