<template>
  <NConfigProvider :theme="naiveTheme">
    <slot />
  </NConfigProvider>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { NConfigProvider, darkTheme } from "naive-ui";
import { useDark } from "@vueuse/core";

const isDark = useDark();
const naiveTheme = computed(() => isDark.value ? darkTheme : null);
</script>